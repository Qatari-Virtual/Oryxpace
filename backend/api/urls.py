from django.urls import path
from . import views

urlpatterns = [
    path('hello/', views.hello_world, name='hello_world'),
    path('stats/', views.get_stats, name='get_stats'),
    path('evaluations/questions/', views.get_questions, name='get_questions'),
    path('evaluations/generate/', views.generate_pdf, name='generate_pdf'),
    path('pilots/lookup/', views.lookup_pilot, name='lookup_pilot'),
]
