import sys
sys.path.append('/home/pi/.local/lib/python3.5/site-packages')
sys.path.append('/usr/lib/python3/dist-packages')

from websocket_server import WebsocketServer

import picamera
import time

import pymysql.cursors

width = 800
height = 600

save_dir = "/var/www/html/camera/pictures/"

cam = picamera.PiCamera()
cam.resolution = (width, height)

def printMessage(client,server):
 print("~ in session ~")

def receivedMessage(client,server,message):
    take_time = time.strftime("%Y%m%d%H%M%S")
    print(take_time + " took a picture.")
    if message:
    	file_name = take_time + ".jpg"
    	save_file = save_dir + file_name
    	cam.capture(save_file)
    	sql = 'insert into pictures (pic_name) values(' + take_time + ');'
    	c.execute(sql)
    	conn.commit()

try:	
	conn = pymysql.connect(
		  user='root',
		  password='rasp0910',
		  host='localhost',
		  charset='utf8mb4',
		  db='piPictures'
	 	)
	c = conn.cursor()
	print("connection successed.")
	
	server = WebsocketServer(5555,host="192.168.1.40")
	print("server started.")
	
	server.set_fn_new_client(printMessage)
	
	server.set_fn_message_received(receivedMessage)
	server.run_forever()
finally:
	conn.close()
	print("\connection closed.")
