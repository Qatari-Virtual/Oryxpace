from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import ScheduledEventViewSet

router = DefaultRouter()
router.register(r'events', ScheduledEventViewSet, basename='mission-event')

urlpatterns = [
    path('', include(router.urls)),
]
