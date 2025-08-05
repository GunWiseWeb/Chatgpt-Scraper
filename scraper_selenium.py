from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
import csv
import time
import math

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    driver.get(URL_TEMPLATE.format(1))
    # Wait for age‐gate and click “I’m over 18”
    try:
        btn = driver.find_element(By.CSS_SELECTOR, "button.age-gate-yes")
        btn.click()
    except:
        pass

    total_items_text = driver.find_element(By.CSS_SELECTOR, ".paging-summary").text
    # e.g. “1–36 of 10000 Results”
    total = int(total_items_text.split("of")[1].split("Results")[0].strip())
    pages = math.ceil(total / 36)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, pages + 1):
            driver.get(URL_TEMPLATE.format(page))
            time.sleep(3)  # let JS run

            items = driver.find_elements(By.CSS_SELECTOR, ".product-item")
            for itm in items:
                brand = itm.find_element(By.CSS_SELECTOR, ".product-name").text
                # product-name typically “Brand Model”
                upc = itm.find_element(By.CSS_SELECTOR, ".upc").text if itm.find_elements(By.CSS_SELECTOR, ".upc") else ""
                mpn = itm.find_element(By.CSS_SELECTOR, ".mpn").text if itm.find_elements(By.CSS_SELECTOR, ".mpn") else ""
                caliber = itm.find_element(By.CSS_SELECTOR, ".caliber").text if itm.find_elements(By.CSS_SELECTOR, ".caliber") else ""
                ftype = itm.find_element(By.CSS_SELECTOR, ".firearm-type").text if itm.find_elements(By.CSS_SELECTOR, ".firearm-type") else ""
                writer.writerow({
                    "Brand/Model": brand,
                    "UPC": upc,
                    "MPN": mpn,
                    "Caliber": caliber,
                    "Type": ftype
                })
    driver.quit()

if __name__ == "__main__":
    main()
