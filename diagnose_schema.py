from pymongo import MongoClient
import json

client = MongoClient('mongodb://localhost:27017/')
db = client['mapeo']

for col_name in ['unete', 'estatales', 'cfe']:
    print(f"\n=== {col_name.upper()} ===")
    total = db[col_name].count_documents({})
    print(f"Total documents: {total}")
    
    # Sample all keys from first 100 docs to be sure
    all_keys = set()
    for doc in db[col_name].find().limit(100):
        all_keys.update(doc.keys())
    
    print("Keys found in first 100 docs:")
    print(sorted(list(all_keys)))
    
    # Check specifically for case variations of Municipio
    variations = ['MUNICIPIO', 'Municipio', 'municipio', 'MUN', 'LOCALIDAD']
    for var in variations:
        count = db[col_name].count_documents({var: {"$exists": True}})
        print(f"Docs with '{var}': {count}")

    # Aggregation test for Municipio
    agg = list(db[col_name].aggregate([
        {"$group": {"_id": "$MUNICIPIO", "count": {"$sum": 1}}},
        {"$sort": {"count": -1}},
        {"$limit": 5}
    ]))
    print("Top 5 MUNICIPIO aggregation:")
    print(agg)
