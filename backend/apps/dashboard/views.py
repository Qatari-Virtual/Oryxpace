from rest_framework.decorators import api_view
from rest_framework.response import Response
from database.models import Pirep, Pilot
from django.db.models import Sum

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
        # Load total PIREPs count
        stats["total_pireps"] = Pirep.objects.count()
        
        # Load approved PIREPs count (status = 1)
        stats["approved_pireps"] = Pirep.objects.filter(status=1).count()
        
        # Load active pilots count
        stats["total_pilots"] = Pilot.objects.count()
        
        # Compute total fleet flight hours (status = 1)
        total_seconds_sum = Pirep.objects.filter(status=1).aggregate(Sum('flighttime'))['flighttime__sum'] or 0
        stats["total_flight_time_hours"] = round(total_seconds_sum / 3600, 1)

        # Retrieve recent 5 flight logs
        recent_pireps = Pirep.objects.select_related('pilot', 'aircraft').order_by('-date', '-id')[:5]
        for p in recent_pireps:
            h = p.flighttime // 3600
            m = (p.flighttime % 3600) // 60
            
            stats["recent_flights"].append({
                "flightnum": p.flightnum,
                "departure": p.departure,
                "arrival": p.arrival,
                "flighttime": p.flighttime,
                "date": p.date.strftime('%Y-%m-%d') if p.date else None,
                "pilot_name": p.pilot.name if p.pilot else "Unknown Pilot",
                "aircraft_name": p.aircraft.name if p.aircraft else "Unknown Aircraft",
                "formatted_flighttime": f"{h:02d}h {m:02d}m"
            })
                
    except Exception as e:
        print(f"Database/ORM error in get_stats: {e}")
        stats["error"] = str(e)
        # Fallback values
        stats["recent_flights"] = [
            {"flightnum": "QRV001", "departure": "OTHH", "arrival": "OMDB", "formatted_flighttime": "01h 15m", "date": "2026-05-30", "pilot_name": "Bumy [QRV]", "aircraft_name": "B77W"},
            {"flightnum": "QRV108", "departure": "OTHH", "arrival": "EGLL", "formatted_flighttime": "07h 10m", "date": "2026-05-30", "pilot_name": "Ayush [QRV]", "aircraft_name": "A35K"},
        ]

    return Response(stats)
