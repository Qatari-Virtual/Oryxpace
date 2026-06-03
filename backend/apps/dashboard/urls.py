from django.urls import path
from . import views

urlpatterns = [
    path('hello/', views.hello_world, name='hello_world'),
    path('', views.get_stats, name='get_stats'), # resolved from /api/stats/
]
