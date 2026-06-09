import jwt
from django.conf import settings
from rest_framework import authentication
from rest_framework import exceptions
from database.models import Pilot
from django.db import connection
import os

class PilotJWTAuthentication(authentication.BaseAuthentication):
    def authenticate(self, request):
        auth_header = request.headers.get('Authorization')
        if not auth_header:
            return None
            
        parts = auth_header.split()
        if len(parts) != 2 or parts[0].lower() != 'bearer':
            return None
            
        token = parts[1]
        
        # If the token matches the 32-char hex static API key pattern, skip JWT decoding
        if len(token) == 32 and all(c in '0123456789abcdefABCDEF' for c in token):
            return None
            
        try:
            payload = jwt.decode(token, settings.SECRET_KEY, algorithms=['HS256'])
        except jwt.ExpiredSignatureError:
            raise exceptions.AuthenticationFailed('Token has expired')
        except jwt.InvalidTokenError:
            raise exceptions.AuthenticationFailed('Invalid token')
            
        pilot_id = payload.get('pilot_id')
        if not pilot_id:
            return None
            
        try:
            # Check ride views expect the Pilot model instance
            pilot = Pilot.objects.get(id=pilot_id, status=1)
        except Pilot.DoesNotExist:
            raise exceptions.AuthenticationFailed('User not found or inactive')
            
        # Dynamically add properties for DRF compatibility
        pilot.is_authenticated = True
        return (pilot, token)

class PilotAPIKeyAuthentication(authentication.BaseAuthentication):
    def authenticate(self, request):
        api_key = request.GET.get('apikey')
        
        if not api_key:
            auth_header = request.headers.get('Authorization')
            if auth_header:
                parts = auth_header.split()
                if len(parts) == 2 and parts[0].lower() == 'bearer':
                    token = parts[1]
                    # Only treat it as API key if it's 32 chars (standard MD5 format)
                    if len(token) == 32:
                        api_key = token
                        
        if not api_key:
            return None
            
        # Get static API key from environment
        cc_api_key = os.getenv('CC_API_KEY')
        if not cc_api_key or api_key != cc_api_key:
            raise exceptions.AuthenticationFailed('Invalid API Key')
            
        # API Key authenticated successfully as system/admin.
        # Find an admin pilot, or fallback to the first active pilot.
        try:
            pilot = Pilot.objects.filter(status=1, callsign='QRV001').first()
            if not pilot:
                pilot = Pilot.objects.filter(status=1).first()
            if not pilot:
                # If there are absolutely no pilots, raise exception
                raise exceptions.AuthenticationFailed('No active pilot available for system auth')
        except Exception as e:
            raise exceptions.AuthenticationFailed(f'Database lookup failed: {str(e)}')
            
        pilot.is_authenticated = True
        pilot.is_system_key = True
        return (pilot, None)

def get_pilot_permissions(pilot_id):
    """
    Helper to fetch a list of permissions for a pilot from the permissions table.
    """
    permissions = []
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT name FROM permissions WHERE userid = %s", [pilot_id])
            rows = cursor.fetchall()
            permissions = [row[0] for row in rows]
    except Exception as e:
        print(f"Error fetching permissions: {e}")
    return permissions

def get_pilot_role(pilot_id):
    """
    Helper to fetch staff role details for a pilot.
    """
    role_info = {"level": "Pilot", "role_name": "Active Pilot"}
    try:
        with connection.cursor() as cursor:
            cursor.execute("SELECT level, role_name FROM staff_roles WHERE user_id = %s LIMIT 1", [pilot_id])
            row = cursor.fetchone()
            if row:
                role_info["level"] = row[0]
                role_info["role_name"] = row[1]
    except Exception as e:
        print(f"Error fetching staff roles: {e}")
    return role_info
