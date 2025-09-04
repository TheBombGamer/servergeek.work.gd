from flask import Flask, request, jsonify
from flask_mysqldb import MySQL
from flask_bcrypt import Bcrypt
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from dotenv import load_dotenv
import os

load_dotenv()

app = Flask(__name__)

app.config['MYSQL_HOST'] = os.getenv('MYSQL_HOST')
app.config['MYSQL_USER'] = os.getenv('MYSQL_USER')
app.config['MYSQL_PASSWORD'] = os.getenv('MYSQL_PASSWORD')
app.config['MYSQL_DB'] = os.getenv('MYSQL_DB')

mysql = MySQL(app)
bcrypt = Bcrypt(app)
limiter = Limiter(key_func=get_remote_address)

@app.route('/register', methods=['POST'])
def register():
    username = request.json['username']
    password = request.json['password']
    hashed_password = bcrypt.generate_password_hash(password).decode('utf-8')

    cur = mysql.connection.cursor()
    cur.execute("INSERT INTO users(username, password) VALUES(%s, %s)", (username, hashed_password))
    mysql.connection.commit()
    cur.close()
    return jsonify({'message': 'User registered successfully!'})

@app.route('/login', methods=['POST'])
def login():
    username = request.json['username']
    password = request.json['password']

    cur = mysql.connection.cursor()
    cur.execute("SELECT * FROM users WHERE username = %s", (username,))
    user = cur.fetchone()
    cur.close()

    if user and bcrypt.check_password_hash(user[1], password):
        return jsonify({'message': 'Login successful!'})
    return jsonify({'message': 'Invalid credentials!'}), 401

@app.route('/nginx_record', methods=['POST'])
@limiter.limit("5 per minute")
def create_nginx_record():
    user_id = request.json['user_id']
    record_name = request.json['record_name']

    cur = mysql.connection.cursor()
    cur.execute("SELECT COUNT(*) FROM nginx_records WHERE user_id = %s", (user_id,))
    count = cur.fetchone()[0]

    if count >= 5:
        return jsonify({'message': 'Record limit reached!'}), 403

    cur.execute("INSERT INTO nginx_records(user_id, record_name) VALUES(%s, %s)", (user_id, record_name))
    mysql.connection.commit()
    cur.close()
    return jsonify({'message': 'Nginx record created successfully!'})

@app.route('/nginx_record/<int:record_id>', methods=['PUT'])
def update_nginx_record(record_id):
    new_record_name = request.json['record_name']
    cur = mysql.connection.cursor()
    cur.execute("UPDATE nginx_records SET record_name = %s WHERE id = %s", (new_record_name, record_id))
    mysql.connection.commit()
    cur.close()
    return jsonify({'message': 'Nginx record updated successfully!'})

if __name__ == '__main__':
    port = int(os.getenv('FLASK_RUN_PORT', 5000))
    app.run(port=port, debug=os.getenv('FLASK_ENV') == 'development')