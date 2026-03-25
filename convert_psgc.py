#!/usr/bin/env python3

import openpyxl
import json
import sys

def convert_psgc_excel_to_json(excel_path, output_path):
    print(f"Reading Excel file: {excel_path}")
    
    # Load the workbook
    wb = openpyxl.load_workbook(excel_path)
    ws = wb['PSGC']
    
    # Dictionary to store hierarchical data
    hierarchy = {}
    
    # Read data from Excel (skip header row)
    rows_read = 0
    for idx, row in enumerate(ws.iter_rows(min_row=2, values_only=True), 1):
        # Columns: 10-digit PSGC, Name, Correspondence Code, Geographic Level, ...
        psgc_code = str(row[0]).strip() if row[0] else ""
        name = str(row[1]).strip() if row[1] else ""
        geo_level = str(row[3]).strip() if row[3] else ""
        
        if not psgc_code or not name:
            continue
        
        # PSGC Code structure: 
        # Region: XXXX00000 (4 digits + 5 zeros)
        # Province: XXXX00000 (first 4) + XXXX00 (province + 2 zeros)
        # City: Full 10 digits with last 2 zeros
        # Barangay: Full 10 digits without zeros
        
        # Track all entries
        if geo_level == 'Reg':
            region_code = psgc_code[:2]  # First 2 digits for region
            if region_code not in hierarchy:
                hierarchy[region_code] = {
                    'code': psgc_code,
                    'name': name,
                    'provinces': {}
                }
        elif geo_level == 'Prov':
            region_code = psgc_code[:2]
            prov_code = psgc_code[:4]
            if region_code not in hierarchy:
                hierarchy[region_code] = {
                    'code': psgc_code[:4] + '00000',
                    'name': 'Unknown Region',
                    'provinces': {}
                }
            if prov_code not in hierarchy[region_code]['provinces']:
                hierarchy[region_code]['provinces'][prov_code] = {
                    'code': psgc_code,
                    'name': name,
                    'cities': {}
                }
        elif geo_level == 'City':
            region_code = psgc_code[:2]
            prov_code = psgc_code[:4]
            city_code = psgc_code[:6]
            
            if region_code not in hierarchy:
                hierarchy[region_code] = {
                    'code': psgc_code[:2] + '00000000',
                    'name': 'Unknown Region',
                    'provinces': {}
                }
            if prov_code not in hierarchy[region_code]['provinces']:
                hierarchy[region_code]['provinces'][prov_code] = {
                    'code': psgc_code[:4] + '00000',
                    'name': 'Unknown Province',
                    'cities': {}
                }
            if city_code not in hierarchy[region_code]['provinces'][prov_code]['cities']:
                hierarchy[region_code]['provinces'][prov_code]['cities'][city_code] = {
                    'code': psgc_code,
                    'name': name,
                    'barangays': {}
                }
        elif geo_level == 'Bgy':
            region_code = psgc_code[:2]
            prov_code = psgc_code[:4]
            city_code = psgc_code[:6]
            bgy_code = psgc_code
            
            if region_code not in hierarchy:
                hierarchy[region_code] = {
                    'code': psgc_code[:2] + '00000000',
                    'name': 'Unknown Region',
                    'provinces': {}
                }
            if prov_code not in hierarchy[region_code]['provinces']:
                hierarchy[region_code]['provinces'][prov_code] = {
                    'code': psgc_code[:4] + '00000',
                    'name': 'Unknown Province',
                    'cities': {}
                }
            if city_code not in hierarchy[region_code]['provinces'][prov_code]['cities']:
                hierarchy[region_code]['provinces'][prov_code]['cities'][city_code] = {
                    'code': psgc_code[:8] + '00',
                    'name': 'Unknown City',
                    'barangays': {}
                }
            
            hierarchy[region_code]['provinces'][prov_code]['cities'][city_code]['barangays'][bgy_code] = {
                'code': psgc_code,
                'name': name
            }
        
        rows_read += 1
    
    print(f"Read {rows_read} records from Excel")
    
    # Convert dictionaries to lists
    psgc_json = []
    for region_code, region_data in sorted(hierarchy.items()):
        region_obj = {
            'code': region_data['code'],
            'name': region_data['name'],
            'provinces': []
        }
        
        for prov_code, prov_data in sorted(region_data['provinces'].items()):
            prov_obj = {
                'code': prov_data['code'],
                'name': prov_data['name'],
                'cities': []
            }
            
            for city_code, city_data in sorted(prov_data['cities'].items()):
                city_obj = {
                    'code': city_data['code'],
                    'name': city_data['name'],
                    'barangays': sorted(list(city_data['barangays'].values()), key=lambda x: x['code'])
                }
                prov_obj['cities'].append(city_obj)
            
            region_obj['provinces'].append(prov_obj)
        
        psgc_json.append(region_obj)
    
    # Save as JSON
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(psgc_json, f, ensure_ascii=False, indent=2)
    
    print(f"✓ Successfully converted Excel to JSON!")
    print(f"  Regions: {len(psgc_json)}")
    total_provinces = sum(len(r['provinces']) for r in psgc_json)
    print(f"  Provinces: {total_provinces}")
    total_cities = sum(len(c) for r in psgc_json for prov in r['provinces'] for c in prov['cities'])
    print(f"  Cities/Municipalities: {total_cities}")
    total_barangays = sum(len(c['barangays']) for r in psgc_json for prov in r['provinces'] for c in prov['cities'])
    print(f"  Barangays: {total_barangays}")
    print(f"  File: {output_path}")

if __name__ == "__main__":
    excel_path = r"c:\laragon\www\survey\storage\app\psgc\PSGC-3Q-2025-Publication-Datafile.xlsx"
    output_path = r"c:\laragon\www\survey\storage\app\psgc\psgc.json"
    
    try:
        convert_psgc_excel_to_json(excel_path, output_path)
        sys.exit(0)
    except Exception as e:
        print(f"Error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
