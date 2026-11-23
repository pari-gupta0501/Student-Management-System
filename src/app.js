const express = require('express');
const path = require('path');

const students = require('./routes/students');
const courses = require('./routes/courses');
const attendance = require('./routes/attendance');
const grades = require('./routes/grades');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

app.get('/api/health', (req, res) => res.json({ status: 'ok', service: 'Student Management System' }));
app.use('/api/students', students);
app.use('/api/courses', courses);
app.use('/api/attendance', attendance);
app.use('/api/grades', grades);

app.use((err, req, res, next) => {
  console.error(err);
  if (err.code === 'ER_DUP_ENTRY') return res.status(409).json({ error: 'Duplicate record' });
  res.status(500).json({ error: 'Database operation failed' });
});

module.exports = app;
