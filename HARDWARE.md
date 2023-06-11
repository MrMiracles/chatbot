# Gebruikte documentatie
- PI documentatie: https://www.raspberrypi.com/documentation/computers/raspberry-pi.html
- Als USB apparaat: https://www.hardill.me.uk/wordpress/2019/11/02/pi4-usb-c-gadget/
- PI Gebruiken als toetsenbord: https://mtlynch.io/key-mime-pi/
	- Keycodes voor toetsenbord: https://github.com/mtlynch/key-mime-pi/blob/904e56b6bf1f76da1abb85f654637da0e3c35fa3/app/js_to_hid.py#L32
- GPIO pins: https://gpiozero.readthedocs.io/en/stable/index.html
	- tutorial: https://projects.raspberrypi.org/en/projects/physical-computing/1

___

- Om het python script te starten als de PI opstart gebruik ik cron: 
    - crontab -e
	- python ~/lukraak.py
____

## Lukraak hardware knopjes code (python)
```
from gpiozero import LED, Button
from time import sleep
from signal import pause

# define led and button
ledRed = LED(23)
buttonRed = Button(24)
ledGreen = LED(25)
buttonGreen = Button(8)

# show that I started
# by flashing the leds

ledRed.on()
ledGreen.on()
sleep(0.5)
ledRed.off()
ledGreen.off()

# define functions for when buttons
# are pressed

def pressed_red():
    with open("/dev/hidg0", "wb+") as f:
            buf = [0] * 8
            buf[2] = 0x2d
            f.write(bytearray(buf))
            f.write(bytearray([0] * 8))
    ledRed.on()
    sleep(3)
    ledRed.off()

def pressed_green():
    with open("/dev/hidg0", "wb+") as f:
            buf = [0] * 8
            buf[0] = 0x20
            buf[2] = 0x2e
            f.write(bytearray(buf))
            f.write(bytearray([0] * 8))
    ledGreen.on()
    sleep(3)
    ledGreen.off()

# bind function to button pressed
buttonRed.when_pressed = pressed_red
buttonGreen.when_pressed = pressed_green

# wait for buttons to be pressed
pause();
```