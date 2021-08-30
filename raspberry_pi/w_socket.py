import sys
import os
import shutil
sys.path.append('/home/pi/.local/lib/python3.5/site-packages')
sys.path.append('/usr/lib/python3/dist-packages')

from websocket_server import WebsocketServer

import picamera
import time

import pymysql.cursors
import boto3

width = 800
height = 600

save_dir = "temp_pictures/"

cam = picamera.PiCamera()
cam.resolution = (width, height)

s3 = boto3.resource('s3');

def printMessage(client,server):
 print("~ in session ~")

def receivedMessage(client,server,message):
    take_time = time.strftime("%Y%m%d%H%M%S")
    print(take_time + " took a picture.")
    file_name = take_time + ".jpg"
    save_file = save_dir + file_name
    cam.capture(save_file)
    bucket.upload_file(save_file, "pictures/" + file_name)
    sql = 'insert into pictures (pic_name) values(' + take_time + ');'
    c.execute(sql)
    conn.commit()

try:
	shutil.rmtree('temp_pictures')
	os.mkdir('temp_pictures')
	conn = pymysql.connect(
		  user='root',
		  password='rasp0910',
		  host='35.76.184.39',
		  charset='utf8mb4',
		  db='piPictures'
	 	)
	c = conn.cursor()
	bucket = s3.Bucket('rasp-camera')

	print("connection successed.")

	server = WebsocketServer(5555,host="192.168.1.40")
	print("server started.")

	server.set_fn_new_client(printMessage)

	server.set_fn_message_received(receivedMessage)
	server.run_forever()
finally:
	conn.close()
	print("\connection closed.")
