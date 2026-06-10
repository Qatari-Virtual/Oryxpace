from django.db import models

class Pilot(models.Model):
    name = models.CharField(max_length=255)
    callsign = models.CharField(max_length=50, unique=True)
    email = models.TextField()
    password = models.TextField()
    ifc = models.TextField()
    status = models.IntegerField(default=1)

    class Meta:
        db_table = 'pilots'
        managed = False

    def __str__(self):
        return f"{self.name} ({self.callsign})"

    @property
    def is_authenticated(self):
        # Allow override dynamically by the auth backend
        return getattr(self, '_is_authenticated', False)

    @is_authenticated.setter
    def is_authenticated(self, value):
        self._is_authenticated = value


class Aircraft(models.Model):
    name = models.CharField(max_length=255)

    class Meta:
        db_table = 'aircraft'
        managed = False

    def __str__(self):
        return self.name

class Pirep(models.Model):
    flightnum = models.CharField(max_length=50)
    departure = models.CharField(max_length=10)
    arrival = models.CharField(max_length=10)
    flighttime = models.IntegerField()
    date = models.DateField()
    status = models.IntegerField()
    pilot = models.ForeignKey(Pilot, db_column='pilotid', on_delete=models.CASCADE)
    aircraft = models.ForeignKey(Aircraft, db_column='aircraftid', on_delete=models.CASCADE, null=True)

    class Meta:
        db_table = 'pireps'
        managed = False

    def __str__(self):
        return f"{self.flightnum}: {self.departure} -> {self.arrival}"


class ScheduledEvent(models.Model):
    title = models.CharField(max_length=200)
    description = models.TextField()
    image_url = models.TextField(null=True, blank=True)
    footer_text = models.TextField(null=True, blank=True)
    color = models.CharField(max_length=50, default='gold')
    author_name = models.TextField(null=True, blank=True)
    flight_numbers = models.TextField(null=True, blank=True)
    custom_emojis = models.TextField(null=True, blank=True)
    multiplier = models.DecimalField(max_digits=5, decimal_places=2, default=1.0)
    deadline_hours = models.IntegerField(default=24)
    channel_id = models.BigIntegerField()
    post_time = models.DateTimeField()
    creator_id = models.BigIntegerField()
    is_posted = models.IntegerField(default=0)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = 'scheduled_events'
        managed = False

    def __str__(self):
        return self.title
