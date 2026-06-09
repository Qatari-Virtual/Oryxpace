from django.urls import path
from . import views

urlpatterns = [
    path('login/', views.login, name='auth_login'),
    path('me/', views.me, name='auth_me'),
]
