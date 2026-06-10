from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path('admin/', admin.site.urls),
    path('api/auth/', include('apps.authentication.urls')),
    path('api/stats/', include('apps.dashboard.urls')),
    path('api/evaluations/', include('apps.checkride.urls')),
    path('api/pilots/', include('apps.checkride.urls')),  # Includes pilot lookup route
    path('api/missions/', include('apps.mission.urls')),
] + static(settings.STATIC_URL, document_root=settings.BASE_DIR.parent / 'assets')
