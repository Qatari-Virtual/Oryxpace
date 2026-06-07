import os
import sys

# Add backend directory to path
sys.path.append(os.path.join(os.path.dirname(__file__), '..', 'backend'))

# Mock django settings
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
import django
django.setup()

from services.pdf_service import generate_evaluation_pdf

mock_data = {
    "trainee_name": "Ayush",
    "trainee_callsign": "QRV002",
    "instructor_name": "Instructor Ayush",
    "instructor_callsign": "QRV001",
    "flight_date": "2026-06-07",
    "general_remarks": "A good flight overall, but needs to focus on UNICOM advisories and cruise altitude constraints.\nLanding and taxiing were excellent.",
    "final_decision": "PASS",
    "previous_score": "70.0",
    "answers": [
        {
            "id": "FP1",
            "category": "Flight Callsign Procedure",
            "points": 1,
            "question": "What was the callsign of the trainee in flight?",
            "selected_option": "Correct callsign used",
            "is_correct": True,
            "is_optional": False,
            "procedure_details": "Callsign for flight will be Qatari {callsign_num}CR."
        },
        {
            "id": "FP2",
            "category": "Main Switch Procedure",
            "points": 1,
            "question": "Was the Main Switch turned on correctly?",
            "selected_option": "Yes, correctly switched on",
            "is_correct": True,
            "is_optional": False,
            "procedure_details": "The Main Switch must be turned on first before starting the engines."
        },
        {
            "id": "FP5",
            "category": "Taxi Announcement Procedure",
            "points": 1,
            "question": "When did the trainee announce Taxi?",
            "selected_option": "There was significant gap between announcement and movement. ⚠️",
            "is_correct": False,
            "is_optional": False,
            "procedure_details": "Trainee to announce Taxi before starting taxi."
        },
        {
            "id": "FP9",
            "category": "Hold Short Line Procedure",
            "points": 1,
            "question": "Was stopping at the Hold Short Line required and completed correctly?",
            "selected_option": "Yes, Hold Short was required and completed correctly ✅",
            "is_correct": True,
            "is_optional": False,
            "procedure_details": "When stopping at HSL, ensure entire aircraft starting from nose should be behind the line and not just the front landing gear"
        },
        {
            "id": "FP10",
            "category": "Hold Short Announcement Procedure",
            "points": 1,
            "question": "If the Trainee made a Hold-Short did they announce holding?",
            "selected_option": "No, they didn't 💀",
            "is_correct": False,
            "is_optional": False,
            "procedure_details": "Hold short should only be announced when another plane is taking off or landing on the runway"
        },
        {
            "id": "FP22",
            "category": "Cruise Altitude Direction Procedure",
            "points": 1,
            "question": "Was the cruise altitude selected in accordance with the assigned flight direction?",
            "selected_option": "Incorrect cruise altitude selected for the flight direction⚠️",
            "is_correct": False,
            "is_optional": False,
            "procedure_details": "If plane is flying Eastbound flight then its Cruise Altitude should be in ODD numbers."
        }
    ]
}

try:
    buffer = generate_evaluation_pdf(mock_data)
    with open("test_out.pdf", "wb") as f:
        f.write(buffer.read())
    print("SUCCESS: PDF generated and saved as test_out.pdf")
except Exception as e:
    import traceback
    traceback.print_exc()
    sys.exit(1)
