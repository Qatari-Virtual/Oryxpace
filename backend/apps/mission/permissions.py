from rest_framework.permissions import BasePermission

class IsMissionManager(BasePermission):
    """
    Allows access only to pilots with callsign numbers 001, 002, 003, or 004.
    """
    def has_permission(self, request, view):
        if not request.user or not request.user.is_authenticated:
            return False
        
        callsign = getattr(request.user, 'callsign', '')
        if not callsign:
            return False
            
        # Extract digits from callsign (e.g. "QRV002" -> "002")
        callsign_num = "".join(filter(str.isdigit, callsign))
        return callsign_num in ["001", "002", "003", "004"]
