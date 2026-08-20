const mysql = require('mysql2/promise');

const connection = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || '92lottery',
    port: process.env.DB_PORT || 3306
});

// const connection = mysql.createPool({
//     host: 'localhost',
//     user: 'admin',
//     password: 'Dph51mO5qkS8U1k',
//     database: '92lottery'
// });

export default connection;