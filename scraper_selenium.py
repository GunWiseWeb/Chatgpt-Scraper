# File: scraper_selenium.py

import time
import csv
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Auto-install matching chromedriver
chromedriver_autoinstaller.install()

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def dismiss_popup(driver, wait):
    """Attempt to close any email-subscribe or marketing modal."""
    selectors = [
        "button.age-gate-no",        # in case a deny button appears
        "button.age-gate-yes",       # if age gate still shows
        "button[aria-label='Close']",
        ".modal-close", 
        ".mfp-close", 
        ".fancybox-close", 
        "button.close"
    ]
    for sel in selectors:
        try:
            btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, sel)))
            btn.click()
            print(f"🔒 Dismissed popup via {sel}", flush=True)
            time.sleep(1)
            break
        except:
            continue

def main():
    print("🚀 Starting scraper", flush=True)
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait = WebDriverWait(driver, 15)

    # Seed age-gate cookie before loading any page
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name": "hasVerifiedAge", "value": "true", "domain": "www.rkguns.com"})
    print("🔓 Age gate cookie set", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES + 1):
            print(f"→ Loading page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))

            # First, dismiss any overlay
            dismiss_popup(driver, wait)

            # Wait for the grid to populate
            try:
                wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, ".product-item")))
            except:
                print(f"⚠️  No products appeared on page {page}", flush=True)
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
