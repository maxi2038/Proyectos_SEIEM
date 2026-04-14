from django.shortcuts import render
from pymongo import MongoClient
from django.contrib import messages
from django.http import HttpResponse, JsonResponse
from django.views.decorators.csrf import csrf_exempt
import csv
import json
import io
import codecs

# Conexión a la base de datos 'internet'
client = MongoClient('mongodb://localhost:27017/')
db = client['mapeo']

def menu(request):
    total_unete = db['unete'].count_documents({})
    total_cfe = db['cfe'].count_documents({})
    total_federales = db['estatales'].count_documents({})
    
    context = {
        'totales': {
            'unete': total_unete,
            'cfe': total_cfe,
            'federales': total_federales,
        }
    }
    return render(request, 'menu.html', context)

def ver_mapa(request):
    # obtener lista de colecciones seleccionadas
    if not request.GET:
        colecciones = []
    else:
        colecciones = request.GET.getlist('coleccion')

    # Removed default 'unete' selection to start empty
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
            # Handle mixed types (int vs str) in DB
            if zona_escolar.isdigit():
                filtro['ZONA ESCOLAR'] = {'$in': [zona_escolar, int(zona_escolar)]}
            else:
                filtro['ZONA ESCOLAR'] = zona_escolar
        if nivel_educativo:
            filtro['NIVEL EDUCATIVO'] = nivel_educativo
        if buscar:
            filtro['$or'] = [
                {'CCT': {'$regex': buscar, '$options': 'i'}},
                {'NOMBRE DEL CT': {'$regex': buscar, '$options': 'i'}}
            ]
        
        # Ensure coordinates exist
        filtro['LATITUD'] = {'$ne': None}
        filtro['LONGITUD'] = {'$ne': None}

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
                'SECTOR EDUCATIVO': 1,
                'NIVEL EDUCATIVO': 1,
                'TURNO': 1,
                'LATITUD': 1,
                'LONGITUD': 1,
                'DOMICILIO': 1,
                'NOMBRE DEL DIRECTOR': 1,
                'NO TELEFONICO DEL DIRECTOR': 1,
                'ESTADO': 1
            }
        datos_coleccion = list(collection.find(filtro, campos))
        datos_validos = []

        for d in datos_coleccion:
            d['COLECCION'] = coleccion
            if 'NOMBRE DEL CT' in d:
                d['NOMBRE_CT'] = d['NOMBRE DEL CT']

            # Procesar estadísticas CFE (si corresponde)
            if coleccion == 'cfe':
                resp = d.get('INTERNET PARA TODOS CFE 4G (CPE)', '').strip().upper()
                if resp == 'INSTALADA':
                    total_instaladas += 1
                else:
                    total_no_instaladas += 1

            # Sanitización de Coordenadas
            if 'LATITUD' in d and 'LONGITUD' in d and d['LATITUD'] is not None and d['LONGITUD'] is not None:
                try:
                    lat = float(d['LATITUD'])
                    lon = float(d['LONGITUD'])
                    
                    # Corregir longitud positiva (error común: 99.6 -> -99.6)
                    if lon > 0:
                        lon = lon * -1
                        d['LONGITUD'] = lon # Actualizar en el objeto
                        lat = lat # Mantener latitud
                    
                    # Validar que esté dentro de una caja geográfica razonable (EdoMex extendido)
                    # Lat: 18.0 a 21.0, Lon: -101.5 a -98.0
                    if 18.0 <= lat <= 21.0 and -101.5 <= lon <= -98.0:
                        datos_validos.append(d)
                except (ValueError, TypeError):
                    continue
        
        datos.extend(datos_validos)

    # Para los selects
    municipios = []
    zonas = []
    niveles = []
    # Para los selects - SIEMPRE buscar en todas las colecciones para no perder opciones
    municipios = []
    zonas = []
    niveles = []
    for coleccion_nombre in ['unete', 'cfe', 'estatales']:
        collection = db[coleccion_nombre]
        municipios += collection.distinct('MUNICIPIO')
        zonas += collection.distinct('ZONA ESCOLAR')
        niveles += collection.distinct('NIVEL EDUCATIVO')
    municipios_raw = sorted(set(municipios))
    zonas_raw = sorted(set(zonas))
    niveles_raw = sorted(set(niveles))

    # Pre-process lists to handle selection in backend (avoids template syntax issues with formatters)
    municipios_list = [{'nombre': m, 'selected': m == municipio} for m in municipios_raw]
    zonas_list = [{'nombre': z, 'selected': z == zona_escolar} for z in zonas_raw]
    niveles_list = [{'nombre': n, 'selected': n == nivel_educativo} for n in niveles_raw]

    # Serialize datos to JSON to avoid JS syntax errors (None vs null)
    datos_json = json.dumps(datos, default=str)

    context = {
        'datos': datos,
        'datos_json': datos_json,
        'coleccion': colecciones[0] if len(colecciones) == 1 else '',
        'colecciones': colecciones,
        'unete_checked': 'checked' if 'unete' in colecciones else '',
        'cfe_checked': 'checked' if 'cfe' in colecciones else '',
        'estatales_checked': 'checked' if 'estatales' in colecciones else '',
        'municipios': municipios_list,
        'zonas': zonas_list,
        'niveles': niveles_list,
        'total_instaladas': total_instaladas,
        'total_no_instaladas': total_no_instaladas,
        'buscar': buscar
    }
    return render(request, 'ver_mapa_fixed.html', context)


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

    # Serialize aggregations
    def serialize(data):
        return json.dumps(data, default=str)

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
        # JSON Serialized Data for Charts
        'municipios_unete_json': serialize(municipios_unete),
        'municipios_cfe_json': serialize(municipios_cfe),
        'municipios_federales_json': serialize(municipios_federales),
        'niveles_unete_json': serialize(niveles_unete),
        'niveles_cfe_json': serialize(niveles_cfe),
        'niveles_federales_json': serialize(niveles_federales),
        
        # Keep original logic if needed somewhere else, but charts will use JSON
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


# -------- FUNCIONES DE DESCARGA (todos los campos) -------- #
def _descargar_coleccion(nombre_coleccion, nombre_archivo):
    """Exporta TODOS los campos de una colección a CSV con headers dinámicos."""
    documentos = list(db[nombre_coleccion].find({}, {'_id': 0}))
    response = HttpResponse(content_type='text/csv; charset=utf-8')
    response['Content-Disposition'] = f'attachment; filename="{nombre_archivo}"'
    # BOM para que Excel lo abra bien con acentos
    response.write('\ufeff')

    if not documentos:
        return response

    # Recopilar todos los campos únicos manteniendo el orden del primer documento
    todos_campos = []
    campos_vistos = set()
    for doc in documentos:
        for campo in doc.keys():
            if campo not in campos_vistos:
                todos_campos.append(campo)
                campos_vistos.add(campo)

    writer = csv.DictWriter(response, fieldnames=todos_campos, extrasaction='ignore')
    writer.writeheader()
    for doc in documentos:
        writer.writerow(doc)

    return response


def descargar_unete(request):
    return _descargar_coleccion('unete', 'unete.csv')


def descargar_cfe(request):
    return _descargar_coleccion('cfe', 'cfe.csv')


def descargar_estatales(request):
    return _descargar_coleccion('estatales', 'estatales.csv')


# -------- CARGA MASIVA (BULK IMPORT) -------- #
def _cargar_coleccion(request, nombre_coleccion):
    """Procesa un CSV y hace upsert en MongoDB usando CCT como llave única."""
    if request.method != 'POST':
        return JsonResponse({'error': 'método no permitido'}, status=405)

    archivo = request.FILES.get('archivo')
    if not archivo:
        return JsonResponse({'error': 'No se envió ningún archivo.'}, status=400)

    if not archivo.name.endswith('.csv'):
        return JsonResponse({'error': 'Solo se aceptan archivos CSV.'}, status=400)

    try:
        # Detectar encoding (UTF-8 con o sin BOM, luego latin-1)
        contenido_bytes = archivo.read()
        for enc in ('utf-8-sig', 'utf-8', 'latin-1', 'cp1252'):
            try:
                contenido = contenido_bytes.decode(enc)
                break
            except UnicodeDecodeError:
                continue
        else:
            return JsonResponse({'error': 'No se pudo detectar el encoding del archivo.'}, status=400)

        reader = csv.DictReader(io.StringIO(contenido))
        if not reader.fieldnames:
            return JsonResponse({'error': 'El archivo CSV está vacío o no tiene encabezados.'}, status=400)

        coleccion = db[nombre_coleccion]
        insertados = 0
        actualizados = 0
        errores = 0
        errores_detalle = []

        for i, fila in enumerate(reader, start=2):  # start=2 porque fila 1 es header
            cct = fila.get('CCT', '').strip()
            if not cct:
                errores += 1
                errores_detalle.append(f'Fila {i}: CCT vacío, se omitió.')
                continue

            # Limpiar valores vacios
            doc = {k.strip(): v.strip() if isinstance(v, str) else v
                   for k, v in fila.items() if k}

            resultado = coleccion.update_one(
                {'CCT': cct},
                {'$set': doc},
                upsert=True
            )
            if resultado.upserted_id:
                insertados += 1
            else:
                actualizados += 1

        return JsonResponse({
            'ok': True,
            'coleccion': nombre_coleccion,
            'insertados': insertados,
            'actualizados': actualizados,
            'errores': errores,
            'errores_detalle': errores_detalle[:10],  # máx 10 para no saturar
        })

    except Exception as e:
        return JsonResponse({'error': f'Error al procesar el archivo: {str(e)}'}, status=500)


@csrf_exempt
def cargar_unete(request):
    return _cargar_coleccion(request, 'unete')


@csrf_exempt
def cargar_cfe(request):
    return _cargar_coleccion(request, 'cfe')


@csrf_exempt
def cargar_estatales(request):
    return _cargar_coleccion(request, 'estatales')
