from rest_framework import serializers
from database.models import ScheduledEvent

class ScheduledEventSerializer(serializers.ModelSerializer):
    class Meta:
        model = ScheduledEvent
        fields = '__all__'
        read_only_fields = ['creator_id', 'is_posted']
