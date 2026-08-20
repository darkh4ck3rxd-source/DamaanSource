const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

async function importDatabase() {
    const config = {
        host: process.env.MYSQLHOST || process.env.DB_HOST || 'localhost',
        user: process.env.MYSQLUSER || process.env.DB_USER || 'root',
        password: process.env.MYSQLPASSWORD || process.env.DB_PASSWORD || '',
        database: process.env.MYSQLDATABASE || process.env.DB_NAME || 'railway',
        port: process.env.MYSQLPORT || process.env.DB_PORT || 3306,
        multipleStatements: true
    };

    console.log('Connecting to database with:', {
        host: config.host,
        user: config.user,
        database: config.database,
        port: config.port
    });

    try {
        const connection = await mysql.createConnection(config);
        
        // Check if users table already exists
        const [rows] = await connection.query("SHOW TABLES LIKE 'users'");
        if (rows.length > 0) {
            console.log('✅ Database already imported. Skipping import.');
            await connection.end();
            process.exit(0);
        }

        console.log('Reading db.sql...');
        const sql = fs.readFileSync(path.join(__dirname, 'db.sql'), 'utf8');

        console.log('Importing data (this may take a minute)...');
        await connection.query(sql);
        console.log('✅ Database imported successfully!');
        await connection.end();
    } catch (err) {
        console.error('⚠️ Notice during import (continuing startup):', err.message);
        process.exit(0); // Exit with 0 so server startup proceeds
    }
}

importDatabase();
