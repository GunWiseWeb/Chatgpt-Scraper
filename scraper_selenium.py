# File: scraper_selenium.py

import csv
import chromedriver_autoinstaller
import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# auto-install the matching Chromedriver
chromedriver_autoinstaller.install()

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278  # fixed total page count

def main():
    print("🚀 Starting scraper", flush=True)

    opts = Options()
    opts.add_argument("--headless")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=opts)
    wait = WebDriverWait(driver, 15)

    # seed the age-gate cookie before hitting any page
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})
    print("🔓 Age gate cookie set", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES+1):
            print(f"→ Loading page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))

            # wait for the item‐name elements to appear
            try:
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".cio-item-name")))
            except:
                print(f"⚠️  No products on page {page}", flush=True)
                continue

            # grab all names
            name_elems = driver.find_elements(By.CSS_SELECTOR, ".cio-item-name")
            print(f"   Found {len(name_elems)} items", flush=True)

            for name_elem in name_elems:
                brand_model = name_elem.text.strip()

                # navigate up to the product container
                container = name_elem.find_element(By.XPATH, "./ancestor::div[contains(@class,'cio-item')]")

                # these selectors may need adjustment if the site uses different classes now
                upc = container.get_attribute("data-upc") or ""
                mpn = container.get_attribute("data-mpn") or ""
                caliber = ""
                ftype = ""

                # if caliber & type are visible in sub-elements, adjust these:
                try:
                    caliber = container.find_element(By.CSS_SELECTOR, ".cio-item-caliber").text.strip()
                except:
                    pass
                try:
                    ftype = container.find_element(By.CSS_SELECTOR, ".cio-item-type").text.strip()
                except:
                    pass

                writer.writerow({
                    "Brand/Model": brand_model,
                    "UPC": upc,
                    "MPN": mpn,
                    "Caliber": caliber,
                    "Type": ftype
                })

    driver.quit()
    print("✅ Scraping complete", flush=True)

if __name__ == "__main__":
    main()
