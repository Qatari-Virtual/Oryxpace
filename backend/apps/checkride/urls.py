from django.urls import path
from . import views

urlpatterns = [
    path('questions/', views.get_questions, name='get_questions'),
    path('generate/', views.generate_pdf, name='generate_pdf'),
    path('lookup/', views.lookup_pilot, name='lookup_pilot'),
]
