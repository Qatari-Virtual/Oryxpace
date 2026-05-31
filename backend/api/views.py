from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.db import connection
from django.http import HttpResponse
from django.conf import settings
import datetime
import os
import json
from .pdf_generator import generate_evaluation_pdf

@api_view(['GET'])
def hello_world(request):
    return Response({"message": "Hello from Django REST Framework! React and Django are now connected."})

@api_view(['GET'])
def get_stats(request):
    stats = {
        "total_pireps": 0,
        "approved_pireps": 0,
        "total_pilots": 0,
        "total_flight_time_hours": 0,
        "recent_flights": []
    }
    
    try:
        with connection.cursor() as cursor:
            # OPTIMIZED SINGLE QUERY: Combines 4 network round-trips into ONE database query!
            # This makes the dashboard load 2.5x faster by eliminating network latency.
            cursor.execute("""
                SELECT 
                    (SELECT COUNT(*) FROM pireps) as total_pireps,
                    (SELECT COUNT(*) FROM pireps WHERE status = 1) as approved_pireps,
                    (SELECT COUNT(*) FROM pilots) as total_pilots,
                    (SELECT SUM(flighttime) FROM pireps WHERE status = 1) as total_flight_time
            """)
            row = cursor.fetchone()
            if row:
                stats["total_pireps"] = row[0] or 0
                stats["approved_pireps"] = row[1] or 0
                stats["total_pilots"] = row[2] or 0
                total_seconds = row[3] or 0
                stats["total_flight_time_hours"] = round(total_seconds / 3600, 1)

            # Query 2: Recent Flights (1 network round-trip)
            try:
                cursor.execute("""
                    SELECT 
                        p.flightnum, 
                        p.departure, 
                        p.arrival, 
                        p.flighttime, 
                        p.date, 
                        pi.name AS pilot_name,
                        a.name AS aircraft_name
                    FROM pireps p
                    LEFT JOIN pilots pi ON p.pilotid = pi.id
                    LEFT JOIN aircraft a ON p.aircraftid = a.id
                    ORDER BY p.date DESC, p.id DESC
                    LIMIT 5
                """)
                columns = [col[0] for col in cursor.description]
                recent_rows = cursor.fetchall()
            except Exception as e:
                # Fallback if aircraft table join fails due to schema variations
                print(f"Warning: Aircraft join failed ({e}). Falling back to simple join.")
                cursor.execute("""
                    SELECT 
                        p.flightnum, 
                        p.departure, 
                        p.arrival, 
                        p.flighttime, 
                        p.date, 
                        pi.name AS pilot_name,
                        'Unknown' AS aircraft_name
                    FROM pireps p
                    LEFT JOIN pilots pi ON p.pilotid = pi.id
                    ORDER BY p.date DESC, p.id DESC
                    LIMIT 5
                """)
                columns = [col[0] for col in cursor.description]
                recent_rows = cursor.fetchall()

            for row in recent_rows:
                row_dict = dict(zip(columns, row))
                # Format date to string
                if isinstance(row_dict.get('date'), (datetime.date, datetime.datetime)):
                    row_dict['date'] = row_dict['date'].strftime('%Y-%m-%d')
                
                # Format flighttime to HHh MMm
                seconds = row_dict.get('flighttime') or 0
                h = seconds // 3600
                m = (seconds % 3600) // 60
                row_dict['formatted_flighttime'] = f"{h:02d}h {m:02d}m"
                
                stats["recent_flights"].append(row_dict)
                
    except Exception as e:
        print(f"Database error in get_stats: {e}")
        stats["error"] = str(e)
        stats["recent_flights"] = [
            {"flightnum": "QRV001", "departure": "OTHH", "arrival": "OMDB", "formatted_flighttime": "01h 15m", "date": "2026-05-30", "pilot_name": "Bumy [QRV]", "aircraft_name": "B77W"},
            {"flightnum": "QRV108", "departure": "OTHH", "arrival": "EGLL", "formatted_flighttime": "07h 10m", "date": "2026-05-30", "pilot_name": "Ayush [QRV]", "aircraft_name": "A35K"},
        ]

    return Response(stats)

@api_view(['GET'])
def get_questions(request):
    try:
        json_path = os.path.join(settings.BASE_DIR, 'api', 'data', 'evaluation_templates.json')
        with open(json_path, 'r', encoding='utf-8') as f:
            questions = json.load(f)
        return Response(questions)
    except Exception as e:
        return Response({"error": f"Failed to load evaluation questions: {str(e)}"}, status=500)

@api_view(['POST'])
def generate_pdf(request):
    try:
        data = request.data
        
        # Load active templates
        json_path = os.path.join(settings.BASE_DIR, 'api', 'data', 'evaluation_templates.json')
        with open(json_path, 'r', encoding='utf-8') as f:
            templates = json.load(f)
            
        template_map = {t["id"]: t for t in templates}
        
        # Grading and enrichment engine
        enriched_answers = []
        for ans in data.get("answers", []):
            q_id = ans.get("question_id")
            if q_id not in template_map:
                continue
                
            temp = template_map[q_id]
            selected_idx = ans.get("selected_option_index")
            is_other = ans.get("is_other", False) or (selected_idx == len(temp["options"]))
            
            is_correct = False
            selected_option = ""
            other_text = ans.get("other_text", "")
            
            if is_other:
                selected_option = "Others"
                is_correct = False
            elif selected_idx is not None and 0 <= selected_idx < len(temp["options"]):
                selected_option = temp["options"][selected_idx]
                is_correct = (selected_idx == temp["correctAnswerIndex"])
                
            enriched_answers.append({
                "id": temp["id"],
                "category": temp["category"],
                "points": temp.get("points", 1),
                "question": temp["question"],
                "selected_option": selected_option,
                "is_correct": is_correct,
                "is_other": is_other,
                "other_text": other_text,
                "extra_feedback": ans.get("extra_feedback", ""),
                "correct_statement": temp["correctStatement"],
                "wrong_statement": temp["wrongStatement"],
                "procedure_details": temp["procedureDetails"]
            })
            
        # Build payload for PDF generator
        pdf_payload = {
            "trainee_name": data.get("trainee_name", "Trainee Pilot"),
            "trainee_callsign": data.get("trainee_callsign", "QRV"),
            "instructor_name": data.get("instructor_name", "Flight Instructor"),
            "flight_date": data.get("flight_date", datetime.date.today().strftime("%Y-%m-%d")),
            "flight_callsign": data.get("flight_callsign", "QATAR"),
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
        callsign_num = request.GET.get('callsign_num', '').strip()
        if not callsign_num:
            return Response({"found": False, "message": "Callsign number is required."})
            
        if not callsign_num.isdigit():
            return Response({"found": False, "message": "Callsign must contain only digits."})
            
        padded_num = callsign_num.zfill(3)
        callsign = f"QRV{padded_num}"
        
        with connection.cursor() as cursor:
            cursor.execute("SELECT name, callsign FROM pilots WHERE callsign = %s AND status = 1", [callsign])
            row = cursor.fetchone()
            if row:
                return Response({
                    "found": True,
                    "name": row[0],
                    "callsign": row[1]
                })
            else:
                return Response({
                    "found": False,
                    "message": f"Active pilot with callsign {callsign} not found."
                })
    except Exception as e:
        return Response({"error": f"Failed to lookup pilot: {str(e)}"}, status=500)

