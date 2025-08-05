# File: scraper_selenium.py

import csv
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278  # known total count

def main():
    print("🚀 Starting scraper", flush=True)

    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait = WebDriverWait(driver, 15)

    # Bypass age gate once at page 1
    driver.get(URL_TEMPLATE.format(1))
    try:
        age_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button.age-gate-yes")))
        age_btn.click()
        print("🔓 Age gate bypassed", flush=True)
    except:
        print("🔒 No age gate dialog", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES + 1):
            print(f"→ Loading page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))

            # Wait until at least one product-item is present
            try:
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".product-item")))
            except:
                print(f"⚠️  Timed out waiting for products on page {page}", flush=True)
                continue

            items = driver.find_elements(By.CSS_SELECTOR, ".product-item")
            print(f"   Found {len(items)} items", flush=True)

            for itm in items:
                name = itm.find_element(By.CSS_SELECTOR, ".product-name").text
                upc = itm.find_element(By.CSS_SELECTOR, ".upc").text if itm.find_elements(By.CSS_SELECTOR, ".upc") else ""
                mpn = itm.find_element(By.CSS_SELECTOR, ".mpn").text if itm.find_elements(By.CSS_SELECTOR, ".mpn") else ""
                caliber = itm.find_element(By.CSS_SELECTOR, ".caliber").text if itm.find_elements(By.CSS_SELECTOR, ".caliber") else ""
                ftype = itm.find_element(By.CSS_SELECTOR, ".firearm-type").text if itm.find_elements(By.CSS_SELECTOR, ".firearm-type") else ""
                writer.writerow({
                    "Brand/Model": name,
                    "UPC": upc,
                    "MPN": mpn,
                    "Caliber": caliber,
                    "Type": ftype
                })

    driver.quit()
    print("✅ Scraping complete", flush=True)

if __name__ == "__main__":
    main()
