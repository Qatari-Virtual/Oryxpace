from rest_framework.decorators import api_view, permission_classes, authentication_classes
from rest_framework.response import Response
from rest_framework import exceptions
from django.conf import settings
from database.models import Pilot
from .auth_backend import get_pilot_permissions, get_pilot_role
import bcrypt
import jwt
import datetime

def get_avatar_url(pilot):
    ifc_val = pilot.ifc.strip() if pilot.ifc else ""
    ifc_username = ""
    if ifc_val:
        if 'infiniteflight.com/u/' in ifc_val:
            parts = ifc_val.split('infiniteflight.com/u/')[-1].split('/')
            ifc_username = parts[0]
        elif not ifc_val.startswith('http://') and not ifc_val.startswith('https://'):
            ifc_username = ifc_val

    if ifc_username:
        # Standard Discourse Avatar Template
        return f"https://community.infiniteflight.com/user_avatar/community.infiniteflight.com/{ifc_username.lower()}/90/1.png"
    return f"https://api.dicebear.com/7.x/bottts/svg?seed={pilot.name}"


@api_view(['POST'])
@authentication_classes([])
@permission_classes([])
def login(request):
    email = request.data.get('email', '').strip()
    password = request.data.get('password', '').strip()
    
    if not email or not password:
        return Response({"error": "Email and password are required."}, status=400)
        
    try:
        # Search pilot by email
        pilot = Pilot.objects.get(email=email)
    except Pilot.DoesNotExist:
        return Response({"error": "Invalid email or password."}, status=401)
        
    if pilot.status != 1:
        return Response({"error": "Your pilot account is pending approval or inactive."}, status=403)
        
    # Verify bcrypt password
    stored_hash = pilot.password
    if stored_hash.startswith('$2y$'):
        stored_hash = '$2b$' + stored_hash[4:]
        
    try:
        if not bcrypt.checkpw(password.encode('utf-8'), stored_hash.encode('utf-8')):
            return Response({"error": "Invalid email or password."}, status=401)
    except Exception as e:
        print(f"Error during password check: {e}")
        return Response({"error": "Password verification failed."}, status=500)
        
    # User authenticated successfully. Fetch permissions and roles.
    permissions = get_pilot_permissions(pilot.id)
    role_info = get_pilot_role(pilot.id)
    
    # Create JWT token
    payload = {
        'pilot_id': pilot.id,
        'callsign': pilot.callsign,
        'name': pilot.name,
        'email': pilot.email,
        'permissions': permissions,
        'role': role_info['role_name'],
        'level': role_info['level'],
        'exp': datetime.datetime.utcnow() + datetime.timedelta(days=7)
    }
    
    token = jwt.encode(payload, settings.SECRET_KEY, algorithm='HS256')
    if isinstance(token, bytes):
        token = token.decode('utf-8')
        
    return Response({
        "token": token,
        "user": {
            "id": pilot.id,
            "callsign": pilot.callsign,
            "name": pilot.name,
            "email": pilot.email,
            "permissions": permissions,
            "role": role_info['role_name'],
            "level": role_info['level'],
            "avatar": get_avatar_url(pilot)
        }
    })

@api_view(['GET'])
def me(request):
    if not request.user or not request.user.is_authenticated:
        return Response({"error": "Unauthorized"}, status=401)
        
    pilot = request.user
    permissions = get_pilot_permissions(pilot.id)
    role_info = get_pilot_role(pilot.id)
    
    return Response({
        "id": pilot.id,
        "callsign": pilot.callsign,
        "name": pilot.name,
        "email": pilot.email,
        "permissions": permissions,
        "role": role_info['role_name'],
        "level": role_info['level'],
        "avatar": get_avatar_url(pilot)
    })
