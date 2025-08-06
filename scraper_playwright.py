# File: scraper_playwright.py

import csv
from playwright.sync_api import sync_playwright

LIST_URL    = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS      = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def main():
    print("🐼 scraper_playwright.py starting…", flush=True)
    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True)
        context = browser.new_context()
        # seed age-gate cookie
        context.add_cookies([{
            "name":   "hasVerifiedAge",
            "value":  "true",
            "domain": "www.rkguns.com",
            "path":   "/"
        }])
        page = context.new_page()

        with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
            writer = csv.DictWriter(f, fieldnames=FIELDS)
            writer.writeheader()

            for p in range(1, TOTAL_PAGES + 1):
                print(f"→ Page {p}/{TOTAL_PAGES}", flush=True)
                page.goto(LIST_URL.format(p))

                try:
                    # wait for the AJAX JSON response
                    response = page.wait_for_response(
                        lambda r: "Search-UpdateGrid" in r.url and r.status == 200,
                        timeout=10000
                    )
                    grid = response.json().get("grid", {})
                    products = grid.get("products", [])
                except Exception:
                    print(f"⚠️  No grid JSON on page {p}", flush=True)
                    continue

                print(f"   Captured {len(products)} products", flush=True)
                for prod in products:
                    title = prod.get("pageTitle", "").strip()
                    brand = prod.get("brand", "").strip()
                    model = prod.get("productName", "").strip() or title
                    upc   = prod.get("upc", "")
                    mpn   = prod.get("manufacturerPartNumber", "")
                    attrs = prod.get("attributes", {})
                    caliber = attrs.get("caliber", "")
                    ftype   = attrs.get("firearmtype", "").capitalize()

                    writer.writerow({
                        "Title":      title,
                        "Brand":      brand,
                        "Model Name": model,
                        "UPC":        upc,
                        "MPN":        mpn,
                        "Caliber":    caliber,
                        "Type":       ftype
                    })

        browser.close()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
