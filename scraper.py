# File: scraper_api.py

import requests
import csv
import math

BASE_URL = (
    "https://www.rkguns.com/on/demandware.store/"
    "Sites-rkguns-Site/default/Search-UpdateGrid"
)
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]

def make_session():
    sess = requests.Session()
    sess.headers.update({
        "User-Agent": "Mozilla/5.0 (compatible; InventoryScraper/1.0)",
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "X-Requested-With": "XMLHttpRequest",
        "Referer": "https://www.rkguns.com/firearms.html?page=1&numResults=36"
    })
    # Bypass the age-gate
    sess.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")
    return sess

def fetch_page(sess, offset):
    params = {
        "cgid": "firearms",
        "start": offset,
        "sz": 36,
        "format": "ajax"
    }
    r = sess.get(BASE_URL, params=params)
    r.raise_for_status()
    return r.json()

def main():
    sess = make_session()
    init = fetch_page(sess, 0)
    total = init["grid"]["hits"]
    pages = math.ceil(total / 36)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for i in range(pages):
            offset = i * 36
            print(f"Fetching records {offset+1}–{min(offset+36, total)} of {total}")
            data = fetch_page(sess, offset)
            for prod in data["grid"]["products"]:
                # Combine brand + model/name
                brand = prod.get("brand", "").strip()
                # DW JSON may use pageTitle, productName or name
                model = (
                    prod.get("pageTitle", "").strip()
                    or prod.get("productName", "").strip()
                    or prod.get("name", "").strip()
                )
                bnm = f"{brand}/{model}" if brand and model else (brand or model)
                
                writer.writerow({
                    "Brand/Model": bnm,
                    "UPC": prod.get("upc", ""),
                    "MPN": prod.get("manufacturerPartNumber", ""),
                    "Caliber": prod.get("attributes", {}).get("caliber", ""),
                    "Type": prod.get("attributes", {}).get("firearmtype", "")
                })

if __name__ == "__main__":
    main()
