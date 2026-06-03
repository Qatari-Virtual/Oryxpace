from django.db import models

class Pilot(models.Model):
    name = models.CharField(max_length=255)
    callsign = models.CharField(max_length=50, unique=True)
    status = models.IntegerField(default=1)

    class Meta:
        db_table = 'pilots'
        managed = False

    def __str__(self):
        return f"{self.name} ({self.callsign})"

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
