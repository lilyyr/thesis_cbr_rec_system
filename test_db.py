import mysql.connector

try:
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

    cursor = conn.cursor()
    cursor.execute("SELECT COUNT(*) FROM cases")
    count = cursor.fetchone()[0]

    print(f"✓ Database connection successful!")
    print(f"✓ Found {count} cases in database")

    conn.close()

except Exception as e:
    print(f"✗ Database connection failed: {e}")
