import sys
import os
import shutil
import configparser

sys.path.append('/home/pi/.local/lib/python3.5/site-packages')
sys.path.append('/usr/lib/python3/dist-packages')

from websocket_server import WebsocketServer

import picamera
import time

import pymysql.cursors
import boto3

import piexif

config_ini = configparser.ConfigParser()
config_ini.read('config.ini', encoding='utf-8')

width = 800
height = 600

save_dir = "temp_pictures/"

cam = picamera.PiCamera()
cam.resolution = (width, height)

s3 = boto3.resource('s3')

def printMessage(client,server):
 print("~ in session ~")

def receivedMessage(client,server,message):
    take_time = time.strftime("%Y%m%d%H%M%S")
    print(take_time + " took a picture.")
    file_name = take_time + ".jpg"
    save_file = save_dir + file_name
    cam.capture(save_file)
    piexif.remove(save_file)
    bucket.upload_file(save_file, "pictures/" + file_name)
    sql = 'insert into pictures (pic_name) values(' + take_time + ');'
    c.execute(sql)
    conn.commit()

try:
	shutil.rmtree('temp_pictures')
	os.mkdir('temp_pictures')
	conn = pymysql.connect(
		  user=config_ini['MySQL']['USER'],
		  password=config_ini['MySQL']['PASSWORD'],
		  host=config_ini['MySQL']['HOST'],
		  charset='utf8mb4',
		  db=config_ini['MySQL']['DB']
	 	)
	c = conn.cursor()
	bucket = s3.Bucket(config_ini['S3']['BACKET'])

	print("connection successed.")

	server = WebsocketServer(int(config_ini['LOCAL']['PORT']),host=config_ini['LOCAL']['HOST'])
	print("server started.")

	server.set_fn_new_client(printMessage)

	server.set_fn_message_received(receivedMessage)
	server.run_forever()
finally:
	conn.close()
	print("\connection closed.")
