from django.shortcuts import render
from pymongo import MongoClient
from django.contrib import messages
from django.http import HttpResponse
import csv

# Conexión a la base de datos 'internet'
client = MongoClient('mongodb://localhost:27017/')
db = client['internet']

def menu(request):
    return render(request, 'menu.html')

def ver_mapa(request):
    # obtener lista de colecciones seleccionadas
    colecciones = request.GET.getlist('coleccion')
    if not colecciones:
        colecciones = ['unete']
    municipio = request.GET.get('municipio', '')
    zona_escolar = request.GET.get('zona_escolar', '')
    nivel_educativo = request.GET.get('nivel_educativo', '')
    buscar = request.GET.get('buscar', '')
    datos = []
    total_instaladas = 0
    total_no_instaladas = 0

    for coleccion in colecciones:
        if coleccion not in ['unete', 'estatales', 'cfe']:
            continue
        collection = db[coleccion]
        filtro = {}
        if municipio:
            filtro['MUNICIPIO'] = municipio
        if zona_escolar:
            filtro['ZONA ESCOLAR'] = zona_escolar
        if nivel_educativo:
            filtro['NIVEL EDUCATIVO'] = nivel_educativo
        if buscar:
            filtro['$or'] = [
                {'CCT': {'$regex': buscar, '$options': 'i'}},
                {'NOMBRE DEL CT': {'$regex': buscar, '$options': 'i'}}
            ]

        if coleccion == 'cfe':
            campos = {
                '_id': 0,
                'NIVEL EDUCATIVO': 1,
                'CCT': 1,
                'NOMBRE DEL CT': 1,
                'DOMICILIO': 1,
                'COLONIA': 1,
                'LOCALIDAD': 1,
                'MUNICIPIO': 1,
                'CVE MUNICIPIO': 1,
                'CP': 1,
                'SECTOR EDUCATIVO': 1,
                'ZONA ESCOLAR': 1,
                'TURNO': 1,
                'COORDENADAS': 1,
                'LATITUD': 1,
                'LONGITUD': 1,
                'NOMBRE DEL DIRECTOR O RESPONSABLE DEL CT': 1,
                'NO TELEFONICO DEL DIRECTOR': 1,
                'CORREO ELECTRONICO INSTITUCIONAL': 1,
                'INTERNET PARA TODOS CFE 4G (CPE)': 1,
                'CPE2': 1,
                'AP2': 1,
                'FECHA DE ENTREGA': 1,
                'ESTADO': 1,
                'LINK': 1
            }
        else:
            campos = {
                '_id': 0,
                'NOMBRE DEL CT': 1,
                'CCT': 1,
                'MUNICIPIO': 1,
                'LINK': 1,
                'ZONA ESCOLAR': 1,
                'NIVEL EDUCATIVO': 1,
                'TURNO': 1,
                'LATITUD': 1,
                'LONGITUD': 1,
                'ESTADO': 1
            }
        datos_coleccion = list(collection.find(filtro, campos))
        if coleccion == 'cfe':
            for d in datos_coleccion:
                resp = d.get('INTERNET PARA TODOS CFE 4G (CPE)', '').strip().upper()
                if resp == 'INSTALADA':
                    total_instaladas += 1
                else:
                    total_no_instaladas += 1
        # 🔹 Duplicar "NOMBRE DEL CT" como "NOMBRE_CT" para acceso fácil en JS
        for d in datos_coleccion:
            d['COLECCION'] = coleccion
            if 'NOMBRE DEL CT' in d:
                d['NOMBRE_CT'] = d['NOMBRE DEL CT']
        datos.extend(datos_coleccion)

    # Para los selects
    municipios = []
    zonas = []
    niveles = []
    for coleccion in colecciones:
        collection = db[coleccion]
        if coleccion != 'cfe':
            municipios += collection.distinct('MUNICIPIO')
            zonas += collection.distinct('ZONA ESCOLAR')
            niveles += collection.distinct('NIVEL EDUCATIVO')
    municipios = sorted(set(municipios))
    zonas = sorted(set(zonas))
    niveles = sorted(set(niveles))

    context = {
        'datos': datos,
        'coleccion': colecciones[0] if len(colecciones) == 1 else '',
        'colecciones': colecciones,
        'municipios': municipios,
        'zonas': zonas,
        'niveles': niveles,
        'total_instaladas': total_instaladas,
        'total_no_instaladas': total_no_instaladas,
        'buscar': buscar
    }
    return render(request, 'ver_mapa.html', context)


# DASHBOARD
def dashboard(request):
    # Totales por colección
    total_unete = db['unete'].count_documents({})
    total_cfe = db['cfe'].count_documents({})
    total_federales = db['estatales'].count_documents({})

    # Escuelas instaladas vs pendientes (solo CFE, ejemplo)
    instaladas_cfe = db['cfe'].count_documents({
        'RESPUESTA_INSTITUCION': {'$regex': '^INSTALADAS$', '$options': 'i'}
    })
    pendientes_cfe = total_cfe - instaladas_cfe

    # Municipios
    municipios_unete = list(db['unete'].aggregate([
        {"$group": {"_id": "$MUNICIPIO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    municipios_cfe = list(db['cfe'].aggregate([
        {"$group": {"_id": "$MUNICIPIO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    municipios_federales = list(db['estatales'].aggregate([
        {"$group": {"_id": "$MUNICIPIO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))

    # Zonas escolares
    zonas_unete = list(db['unete'].aggregate([
        {"$group": {"_id": "$ZONA ESCOLAR", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    zonas_cfe = list(db['cfe'].aggregate([
        {"$group": {"_id": "$ZONA ESCOLAR", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    zonas_federales = list(db['estatales'].aggregate([
        {"$group": {"_id": "$ZONA ESCOLAR", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))

    # Niveles educativos por colección
    niveles_unete = list(db['unete'].aggregate([
        {"$group": {"_id": "$NIVEL EDUCATIVO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    niveles_cfe = list(db['cfe'].aggregate([
        {"$group": {"_id": "$NIVEL EDUCATIVO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))
    niveles_federales = list(db['estatales'].aggregate([
        {"$group": {"_id": "$NIVEL EDUCATIVO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}}
    ]))

    # Nivel educativo total (todas colecciones)
    total_por_nivel = {}
    for coleccion in [niveles_unete, niveles_cfe, niveles_federales]:
        for n in coleccion:
            nivel = n['_id'] if n['_id'] else "Sin Nivel"
            total_por_nivel[nivel] = total_por_nivel.get(nivel, 0) + n['count']

    context = {
        'totales': {
            'unete': total_unete,
            'cfe': total_cfe,
            'federales': total_federales,
        },
        'cfe_instaladas_pendientes': {
            'instaladas': instaladas_cfe,
            'pendientes': pendientes_cfe,
        },
        # Municipios
        'municipios_unete': municipios_unete,
        'municipios_cfe': municipios_cfe,
        'municipios_federales': municipios_federales,
        # Zonas
        'zonas_unete': zonas_unete,
        'zonas_cfe': zonas_cfe,
        'zonas_federales': zonas_federales,
        # Niveles educativos
        'niveles_unete': niveles_unete,
        'niveles_cfe': niveles_cfe,
        'niveles_federales': niveles_federales,
        # Total general por nivel
        'total_por_nivel': total_por_nivel,
    }

    return render(request, 'dashboard.html', context)


# -------- FUNCIONES DE DESCARGA -------- #
def descargar_unete(request):
    coleccion = db["unete"].find()
    response = HttpResponse(content_type="text/csv")
    response["Content-Disposition"] = 'attachment; filename="unete.csv"'

    writer = csv.writer(response)
    writer.writerow(["Nombre CT", "Domicilio", "Nivel Educativo", "CCT", "Turno", "Correo Institucional", "Donante"])

    for item in coleccion:
        writer.writerow([
            item.get("NOMBRE DEL CT", ""),
            item.get("DOMICILIO", ""),
            item.get("NIVEL EDUCATIVO", ""),
            item.get("CCT", ""),
            item.get("TURNO", ""),
            item.get("CORREO ELECTRONICO INSTITUCIONAL", ""),
            item.get("NOMBRE DEL DONANTE", ""),
        ])
    return response


def descargar_cfe(request):
    coleccion = db["cfe"].find()
    response = HttpResponse(content_type="text/csv")
    response["Content-Disposition"] = 'attachment; filename="cfe.csv"'

    writer = csv.writer(response)
    writer.writerow(["Nombre CT", "Domicilio", "Nivel Educativo", "CCT", "Turno", "Correo Institucional"])

    for item in coleccion:
        writer.writerow([
            item.get("NOMBRE DEL CT", ""),
            item.get("DOMICILIO", ""),
            item.get("NIVEL EDUCATIVO", ""),
            item.get("CCT", ""),
            item.get("TURNO", ""),
            item.get("CORREO ELECTRONICO INSTITUCIONAL", ""),
        ])
    return response


def descargar_estatales(request):
    coleccion = db["estatales"].find()
    response = HttpResponse(content_type="text/csv")
    response["Content-Disposition"] = 'attachment; filename="estatales.csv"'

    writer = csv.writer(response)
    writer.writerow(["Nombre CT", "Domicilio", "Nivel Educativo", "CCT", "Turno", "Correo Institucional"])

    for item in coleccion:
        writer.writerow([
            item.get("NOMBRE DEL CT", ""),
            item.get("DOMICILIO", ""),
            item.get("NIVEL EDUCATIVO", ""),
            item.get("CCT", ""),
            item.get("TURNO", ""),
            item.get("CORREO ELECTRONICO INSTITUCIONAL", ""),
        ])
    return response
