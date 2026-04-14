import os
# import django
from pymongo import MongoClient
import json

# Setup Django environment (if needed, but pymongo works directly)
# connecting directly for speed
client = MongoClient('mongodb://localhost:27017/')
db = client['mapeo']

collections = ['unete', 'estatales', 'cfe']

for col_name in collections:
    print(f"--- COLLECTION: {col_name} ---")
    # Find specific school
    doc = db[col_name].find_one({'NOMBRE DEL CT': {'$regex': 'JOSEFA ORTIZ DE DOMINGUEZ', '$options': 'i'}, 'MUNICIPIO': 'TOLUCA'})
    
    if doc:
        print(f"FOUND IN {col_name}")
        print(f"CCT: {doc.get('CCT')}")
        print(f"LATITUD: {doc.get('LATITUD')} (Type: {type(doc.get('LATITUD'))})")
        print(f"LONGITUD: {doc.get('LONGITUD')} (Type: {type(doc.get('LONGITUD'))})")
    else:
        print("Not found in this collection")
    print("\n")
