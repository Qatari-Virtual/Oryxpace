from rest_framework import viewsets, mixins
from rest_framework.permissions import IsAuthenticated
from database.models import ScheduledEvent
from .serializers import ScheduledEventSerializer
from .permissions import IsMissionManager

class ScheduledEventViewSet(mixins.ListModelMixin,
                             mixins.CreateModelMixin,
                             mixins.UpdateModelMixin,
                             mixins.RetrieveModelMixin,
                             viewsets.GenericViewSet):
    """
    A viewset that provides list, retrieve, create, and update actions for ScheduledEvents.
    Deletes are excluded due to DB permission constraints.
    """
    queryset = ScheduledEvent.objects.all().order_by('-created_at')
    serializer_class = ScheduledEventSerializer
    permission_classes = [IsAuthenticated, IsMissionManager]

    def perform_create(self, serializer):
        serializer.save(
            creator_id=self.request.data.get('creator_id', self.request.user.id),
            is_posted=0
        )
