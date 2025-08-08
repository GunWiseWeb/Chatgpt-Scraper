import pandas as pd
import glob

# 1) Find all CSV chunk files
files = sorted(glob.glob("inventory_*.csv"))

# 2) Read & concatenate
df = pd.concat((pd.read_csv(f) for f in files), ignore_index=True)

# 3) Write out the merged file
df.to_csv("inventory.csv", index=False)

print(f"Merged {len(files)} files → inventory.csv ({len(df)} rows)")
