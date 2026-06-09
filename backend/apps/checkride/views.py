from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.http import HttpResponse
from django.conf import settings
from database.models import Pilot
import datetime
import os
import json
from services.pdf_service import generate_evaluation_pdf
from apps.authentication.auth_backend import get_pilot_permissions

@api_view(['GET'])
def get_questions(request):
    try:
        json_path = os.path.join(settings.BASE_DIR, 'apps', 'checkride', 'data', 'evaluation_templates.json')
        with open(json_path, 'r', encoding='utf-8') as f:
            questions = json.load(f)
        return Response(questions)
    except Exception as e:
        return Response({"error": f"Failed to load evaluation questions: {str(e)}"}, status=500)

@api_view(['POST'])
def generate_pdf(request):
    try:
        # Check permissions (bypass for system API key)
        if not getattr(request.user, 'is_system_key', False):
            permissions = get_pilot_permissions(request.user.id)
            if 'pirepmanage' not in permissions and 'admin' not in permissions:
                return Response({"error": "You do not have permission to generate evaluation PDFs."}, status=403)

        data = request.data
        
        # Load active templates
        json_path = os.path.join(settings.BASE_DIR, 'apps', 'checkride', 'data', 'evaluation_templates.json')
        with open(json_path, 'r', encoding='utf-8') as f:
            templates = json.load(f)
            
        template_map = {t["id"]: t for t in templates}
        
        # Grading and enrichment engine
        trainee_callsign = data.get("trainee_callsign", "")
        callsign_num = "".join(filter(str.isdigit, trainee_callsign))
        if not callsign_num:
            callsign_num = "___"

        enriched_answers = []
        for ans in data.get("answers", []):
            q_id = ans.get("question_id")
            if q_id not in template_map:
                continue
                
            temp = template_map[q_id]
            selected_idx = ans.get("selected_option_index")
            is_other = ans.get("is_other", False) or (selected_idx == len(temp["options"]))
            
            is_correct = ans.get("is_correct")
            selected_option = ans.get("selected_option")
            other_text = ans.get("other_text", "")
            
            is_optional = ans.get("is_optional")
            if is_optional is None:
                is_optional = (temp.get("optionalAnswerIndex") is not None and selected_idx == temp["optionalAnswerIndex"])
            
            if is_correct is None:
                if is_other:
                    is_correct = False
                elif selected_idx is not None and 0 <= selected_idx < len(temp["options"]):
                    is_correct = (selected_idx == temp["correctAnswerIndex"]) or (temp.get("optionalAnswerIndex") is not None and selected_idx == temp["optionalAnswerIndex"])
            
            if not selected_option:
                if is_other:
                    selected_option = "Others"
                elif selected_idx is not None and 0 <= selected_idx < len(temp["options"]):
                    selected_option = temp["options"][selected_idx]
                
            procedure_details = ans.get("procedure_details", temp["procedureDetails"])
            if "{callsign_num}" in procedure_details:
                procedure_details = procedure_details.replace("{callsign_num}", callsign_num)

            enriched_answers.append({
                "id": temp["id"],
                "category": ans.get("category", temp["category"]),
                "points": ans.get("points", temp.get("points", 1)),
                "question": ans.get("question", temp["question"]),
                "selected_option": selected_option,
                "is_correct": is_correct,
                "is_other": is_other,
                "is_optional": is_optional,
                "other_text": other_text,
                "extra_feedback": ans.get("extra_feedback", ""),
                "procedure_details": procedure_details
            })
            
        # Build payload for PDF generator
        pdf_payload = {
            "trainee_name": data.get("trainee_name", "Trainee Pilot"),
            "trainee_callsign": data.get("trainee_callsign", "QRV"),
            "instructor_name": data.get("instructor_name", "Flight Instructor"),
            "instructor_callsign": data.get("instructor_callsign", "QRV"),
            "flight_date": data.get("flight_date", datetime.date.today().strftime("%Y-%m-%d")),
            "general_remarks": data.get("general_remarks", ""),
            "final_decision": data.get("final_decision", "PASS"),
            "answers": enriched_answers
        }
        
        # Generate the PDF
        pdf_buffer = generate_evaluation_pdf(pdf_payload)
        
        # Prepare HTTP Response with binary file stream
        response = HttpResponse(pdf_buffer.getvalue(), content_type='application/pdf')
        filename = f"Feedback_{pdf_payload['trainee_callsign']}_{pdf_payload['flight_date']}.pdf".replace(" ", "_")
        response['Access-Control-Expose-Headers'] = 'Content-Disposition'
        response['Content-Disposition'] = f'attachment; filename="{filename}"'
        return response
        
    except Exception as e:
        import traceback
        traceback.print_exc()
        return Response({"error": f"Failed to generate evaluation report: {str(e)}"}, status=500)

@api_view(['GET'])
def lookup_pilot(request):
    try:
        # Check permissions (bypass for system API key)
        if not getattr(request.user, 'is_system_key', False):
            permissions = get_pilot_permissions(request.user.id)
            if 'pirepmanage' not in permissions and 'admin' not in permissions:
                return Response({"error": "You do not have permission to lookup pilots."}, status=403)

        callsign_num = request.GET.get('callsign_num', '').strip()
        if not callsign_num:
            return Response({"found": False, "message": "Callsign number is required."})
            
        if not callsign_num.isdigit():
            return Response({"found": False, "message": "Callsign must contain only digits."})
            
        padded_num = callsign_num.zfill(3)
        callsign = f"QRV{padded_num}"
        
        # Using the clean Django ORM Database Model
        try:
            pilot = Pilot.objects.get(callsign=callsign, status=1)
            return Response({
                "found": True,
                "name": pilot.name,
                "callsign": pilot.callsign
            })
        except Pilot.DoesNotExist:
            return Response({
                "found": False,
                "message": f"Active pilot with callsign {callsign} not found."
            })
    except Exception as e:
        return Response({"error": f"Failed to lookup pilot: {str(e)}"}, status=500)
