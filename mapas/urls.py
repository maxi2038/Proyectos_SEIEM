"""mapas URL Configuration

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/3.1/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.contrib import admin
from django.urls import path
from escuelas import views

urlpatterns = [
    path('admin/', admin.site.urls),
    path('', views.menu, name='menu'),
    path('ver-mapa/', views.ver_mapa, name='ver_mapa'),
    path('dashboard/', views.dashboard, name='dashboard'),
    path('descargar/unete/', views.descargar_unete, name='descargar_unete'),
    path('descargar/cfe/', views.descargar_cfe, name='descargar_cfe'),
    path('descargar/estatales/', views.descargar_estatales, name='descargar_estatales'),
    
    
]

