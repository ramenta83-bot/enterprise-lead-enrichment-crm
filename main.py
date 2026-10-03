from flask import Flask, request, jsonify
from flask_cors import CORS
import mysql.connector

app = Flask(__name__)
CORS(app)

MATECCA_SECRET_TOKEN = "MateccaSecretLiveToken2026_Secure"

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="crm_user",
        password="YourStrongSecurePassword", 
        database="saas_crm"
    )

@app.route('/api/v1/leads', methods=['POST'])
def create_lead():
    client_api_key = request.headers.get("X-Matecca-API-Key")
    if client_api_key != MATECCA_SECRET_TOKEN:
        return jsonify({"status": "error", "message": "Unauthorized API Access Blocked"}), 401

    request_data = request.get_json()
    if not request_data:
        return jsonify({"status": "error", "message": "Invalid or missing payload body"}), 400

    name = request_data.get('name')
    email = request_data.get('email')

    if not name or not email:
        return jsonify({"status": "error", "message": "Required fields missing"}), 400

    domain = email.split('@')[-1]
    company_name = domain.split('.')[0].capitalize()

    if domain in ["gmail.com", "yahoo.com", "hotmail.com"]:
        industry = "Unknown (Personal Email)"
        lead_score = 10
    else:
        industry = "B2B Tech Sector"
        lead_score = 85

    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        sql = "INSERT INTO crm_leads (name, email, company, industry, lead_score) VALUES (%s, %s, %s, %s, %s)"
        cursor.execute(sql, (name, email, company_name, industry, lead_score))
        conn.commit()
    except mysql.connector.Error as err:
        return jsonify({"status": "error", "message": "Internal processing conflict"}), 500
    finally:
        if 'cursor' in locals(): cursor.close()
        if 'conn' in locals(): conn.close()

    return jsonify({"status": "success", "score": lead_score}), 200

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8000)
