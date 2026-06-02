import sys
import subprocess

try:
    from PIL import Image
except ImportError:
    subprocess.check_call([sys.executable, "-m", "pip", "install", "pillow"])
    from PIL import Image

def process_image():
    img = Image.open("assets/LOGO_LAVIRAMEAL.png").convert("RGBA")
    datas = img.getdata()
    
    # Get top left pixel as background color reference
    bg = img.getpixel((0,0))
    print(f"Detected background color: {bg}")

    newData = []
    for item in datas:
        # If pixel is very close to the background color (e.g. white), make it transparent
        # Threshold of 15 for near-white/near-bg anti-aliasing
        if abs(item[0] - bg[0]) < 15 and abs(item[1] - bg[1]) < 15 and abs(item[2] - bg[2]) < 15:
            newData.append((255, 255, 255, 0))
        else:
            newData.append(item)
            
    img.putdata(newData)
    img.save("assets/LOGO_LAVIRAMEAL_CLEAN.png", "PNG")
    print("Successfully removed background and saved as LOGO_LAVIRAMEAL_CLEAN.png")

if __name__ == "__main__":
    process_image()
