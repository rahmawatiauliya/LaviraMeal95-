import sys
from PIL import Image

def process_image():
    # Convert to RGBA
    img = Image.open("assets/Lavira.jpeg").convert("RGBA")
    datas = img.getdata()
    
    # Get top left pixel as background color reference
    bg = img.getpixel((0,0))
    print(f"Detected background color for Lavira.jpeg: {bg}")

    newData = []
    # Since it's a JPEG, compression artifacts might make the background not perfectly uniform
    # We use a slightly higher threshold
    threshold = 30
    
    for item in datas:
        if abs(item[0] - bg[0]) < threshold and abs(item[1] - bg[1]) < threshold and abs(item[2] - bg[2]) < threshold:
            # Transparent
            newData.append((255, 255, 255, 0))
        else:
            newData.append(item)
            
    img.putdata(newData)
    img.save("assets/Lavira_CLEAN.png", "PNG")
    print("Successfully processed Lavira.jpeg -> Lavira_CLEAN.png")

if __name__ == "__main__":
    process_image()
