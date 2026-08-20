const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

async function importDatabase() {
    const connection = await mysql.createConnection({
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || '92lottery',
        port: process.env.DB_PORT || 3306,
        multipleStatements: true
    });

    console.log('Reading db.sql...');
    const sql = fs.readFileSync(path.join(__dirname, 'db.sql'), 'utf8');

    console.log('Importing data (this may take a minute)...');
    try {
        await connection.query(sql);
        console.log('✅ Database imported successfully!');
    } catch (err) {
        console.error('❌ Error importing database:', err.message);
    } finally {
        await connection.end();
    }
}

importDatabase();
