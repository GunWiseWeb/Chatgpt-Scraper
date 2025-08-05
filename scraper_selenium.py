# File: scraper_selenium.py

import time
import csv
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

# Auto-install matching chromedriver
chromedriver_autoinstaller.install()

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

    try:
        driver = webdriver.Chrome(options=chrome_opts)
        print("✅ Chrome launched", flush=True)
    except Exception as e:
        print("❌ Failed to launch Chrome:", e, flush=True)
        return

    # Bypass age gate on first page
    driver.get(URL_TEMPLATE.format(1))
    time.sleep(2)
    try:
        btn = driver.find_element(By.CSS_SELECTOR, "button.age-gate-yes")
        btn.click()
        print("🔓 Age gate bypassed", flush=True)
        time.sleep(1)
    except:
        print("🔒 No age gate found", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES + 1):
            print(f"Loading page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))
            time.sleep(3)  # allow JS to render

            items = driver.find_elements(By.CSS_SELECTOR, ".product-item")
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
