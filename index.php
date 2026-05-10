<?php // 

// ── INSERT endpoint for IoT ──
if (isset($_GET['insert'])) {
    header('Content-Type: application/json');
    mysqli_report(MYSQLI_REPORT_OFF);
    $host = getenv('MYSQLHOST')      ?: 'localhost';
    $port = (int)(getenv('MYSQLPORT') ?: 3306);
    $user = getenv('MYSQLUSER')      ?: 'root';
    $pass = getenv('MYSQLPASSWORD')  ?: '';
    $db   = getenv('MYSQL_DATABASE') ?: 'railway';
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$conn->connect_error) {
        $s1 = floatval($_POST['soil1_moisture'] ?? 0);
        $s2 = intval($_POST['soil2_wet']        ?? 0);
        $t  = floatval($_POST['temperature']    ?? 0);
        $h  = floatval($_POST['humidity']       ?? 0);
        $w  = floatval($_POST['water_level_cm'] ?? 0);
        $p  = intval($_POST['pump_status']      ?? 0);
        $stmt = $conn->prepare("INSERT INTO sensor_readings (soil1_moisture,soil2_wet,temperature,humidity,water_level_cm,pump_status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("diiddi",$s1,$s2,$t,$h,$w,$p);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['status'=>'OK']);
    } else {
        echo json_encode(['status'=>'error','msg'=>$conn ? 'not POST' : 'no db']);
    }
    exit;
}

if (isset($_GET['fetch'])) {
    header('Content-Type: application/json');
   mysqli_report(MYSQLI_REPORT_OFF);
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = (int)(getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQL_DATABASE') ?: 'railway';
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) $conn = null;
    $data = ['soil1_moisture'=>0,'soil2_wet'=>0,'temperature'=>0,'humidity'=>0,'water_level_cm'=>0,'pump_status'=>0,'created_at'=>null];
    if ($conn && !$conn->connect_error) {
        $result = $conn->query("SELECT soil1_moisture, soil2_wet, temperature, humidity, water_level_cm, pump_status, created_at FROM sensor_readings ORDER BY created_at DESC LIMIT 1");
        if ($result && $result->num_rows > 0) $data = $result->fetch_assoc();
        $conn->close();
    }
    echo json_encode($data); exit;
}

// ── JSON endpoint for time log history ──
if (isset($_GET['log'])) {
    header('Content-Type: application/json');
    mysqli_report(MYSQLI_REPORT_OFF);
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = (int)(getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQL_DATABASE') ?: 'railway';
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) $conn = null;
    $logs = [];
   if ($conn && !$conn->connect_error) {
        $result = $conn->query("SELECT soil1_moisture, soil2_wet, temperature, humidity, water_level_cm, pump_status, created_at FROM sensor_readings ORDER BY created_at DESC LIMIT 200");
        if ($result && $result->num_rows > 0) while ($row = $result->fetch_assoc()) $logs[] = $row;
        $conn->close();
    }
    echo json_encode($logs); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Shiggy's Irrigation · Field Monitor</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
:root {
  --bg:       #f0f7ee;
  --bg2:      #ffffff;
  --bg3:      #e8f4e5;
  --surface:  #ffffff;
  --border:   #d4e8cf;
  --border2:  #b8d9b1;

  --green:    #2d9e5f;
  --green2:   #3dbf74;
  --green3:   #7ddba0;
  --greenlt:  #e6f7ed;

  --blue:     #1a8fc4;
  --blue2:    #35aadf;
  --bluelt:   #e3f4fc;

  --orange:   #e07820;
  --orange2:  #f59d45;
  --orangelt: #fff0e0;

  --yellow:   #d4a017;
  --yellowlt: #fef9e7;

  --red:      #e03d3d;
  --redlt:    #fdeaea;

  --purple:   #7c4dbc;
  --purplelt: #f2ecfb;

  --text:     #1a2e1a;
  --text2:    #3d5c3a;
  --text3:    #6b8f68;
  --text4:    #9ab899;

  --shadow:   0 2px 12px rgba(45,158,95,0.10);
  --shadow2:  0 8px 32px rgba(45,158,95,0.14);
  --r:        18px;
  --r2:       12px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* ── Decorative blobs ── */
body::before {
  content: '';
  position: fixed;
  top: -120px; right: -120px;
  width: 480px; height: 480px;
  background: radial-gradient(circle, rgba(61,191,116,0.18) 0%, transparent 70%);
  pointer-events: none; z-index: 0; border-radius: 50%;
}
body::after {
  content: '';
  position: fixed;
  bottom: -80px; left: -80px;
  width: 360px; height: 360px;
  background: radial-gradient(circle, rgba(26,143,196,0.12) 0%, transparent 70%);
  pointer-events: none; z-index: 0; border-radius: 50%;
}

/* ════════════════ HEADER ════════════════ */
header {
  position: sticky; top: 0; z-index: 200;
  background: rgba(255,255,255,0.88);
  border-bottom: 1.5px solid var(--border);
  padding: 0 28px;
  height: 66px;
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  backdrop-filter: blur(20px);
  box-shadow: 0 2px 16px rgba(45,158,95,0.08);
}

.brand { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }

.brand-logo {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, var(--green), var(--green2));
  border-radius: 13px;
  display: grid; place-items: center; font-size: 1.3rem;
  box-shadow: 0 4px 14px rgba(45,158,95,0.35);
}

.brand-name {
  font-size: 1.2rem; font-weight: 900;
  color: var(--green);
  letter-spacing: -0.02em;
}

.brand-sub {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.55rem; color: var(--text3);
  letter-spacing: 0.08em; text-transform: uppercase;
  margin-top: 1px;
}

.header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.nav-tabs {
  display: flex;
  background: var(--bg3);
  border-radius: 12px; padding: 4px;
  border: 1.5px solid var(--border);
}

.nav-tab {
  padding: 7px 18px; border: none; border-radius: 9px;
  background: transparent; color: var(--text3);
  font-family: 'Nunito', sans-serif; font-size: 0.8rem; font-weight: 700;
  cursor: pointer; transition: all 0.22s;
}
.nav-tab.active {
  background: var(--green); color: #fff;
  box-shadow: 0 3px 12px rgba(45,158,95,0.35);
}

.live-badge {
  display: flex; align-items: center; gap: 7px;
  padding: 7px 14px;
  background: var(--greenlt);
  border: 1.5px solid var(--border2);
  border-radius: 100px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.62rem; color: var(--green); font-weight: 700;
  letter-spacing: 0.06em;
}

.live-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--green2);
  animation: livepulse 2s ease-in-out infinite;
}

@keyframes livepulse {
  0%,100% { transform: scale(1); opacity: 1; box-shadow: 0 0 0 0 rgba(61,191,116,0.5); }
  50% { transform: scale(1.3); opacity: 0.8; box-shadow: 0 0 0 5px rgba(61,191,116,0); }
}

.dn-filter-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 14px;
  background: var(--bg3); border: 1.5px solid var(--border);
  border-radius: 100px;
  font-family: 'Nunito', sans-serif; font-size: 0.75rem; font-weight: 700;
  color: var(--text2); cursor: pointer; transition: all 0.22s;
}
.dn-filter-btn.day-active   { background: var(--yellowlt); border-color: #e8c84a; color: var(--yellow); }
.dn-filter-btn.night-active { background: var(--bluelt);   border-color: #8acde8; color: var(--blue); }
.dn-filter-btn.all-active   { background: var(--greenlt);  border-color: var(--border2); color: var(--green); }

.export-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 14px;
  background: var(--bg2); border: 1.5px solid var(--border);
  border-radius: 100px;
  font-family: 'Nunito', sans-serif; font-size: 0.75rem; font-weight: 700;
  color: var(--text2); cursor: pointer; transition: all 0.22s;
}
.export-btn:hover { background: var(--greenlt); border-color: var(--green2); color: var(--green); }

/* ════════════════ LAYOUT ════════════════ */
.wrap {
  max-width: 1260px; margin: 0 auto;
  padding: 28px 22px 80px;
  position: relative; z-index: 1;
}

.page-hero {
  display: flex; align-items: flex-end; justify-content: space-between;
  margin-bottom: 24px; gap: 14px; flex-wrap: wrap;
}

.hero-eyebrow {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem; color: var(--text3);
  letter-spacing: 0.14em; text-transform: uppercase;
  margin-bottom: 4px; display: flex; align-items: center; gap: 8px;
}
.hero-eyebrow::before { content: ''; width: 16px; height: 2px; background: var(--green2); border-radius: 2px; display: inline-block; }

.hero-title {
  font-size: 2.6rem; font-weight: 900;
  line-height: 1; letter-spacing: -0.03em;
  color: var(--text);
}
.hero-title .accent { color: var(--green); }

.hero-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.ts-block {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.58rem; color: var(--text3);
  text-align: right; line-height: 2;
}
.ts-val { color: var(--text2); font-weight: 500; }

.panel { display: none; }
.panel.active { display: block; }

/* ════════════════ CARD BASE ════════════════ */
.card {
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: var(--r);
  padding: 22px;
  position: relative; overflow: hidden;
  box-shadow: var(--shadow);
  transition: box-shadow 0.25s, transform 0.22s, border-color 0.22s;
}
.card:hover {
  box-shadow: var(--shadow2);
  transform: translateY(-2px);
  border-color: var(--border2);
}

/* Top accent stripe */
.acc { position: absolute; top: 0; left: 0; right: 0; height: 4px; border-radius: var(--r) var(--r) 0 0; }
.acc-green  { background: linear-gradient(90deg, var(--green), var(--green2), var(--green3)); }
.acc-blue   { background: linear-gradient(90deg, var(--blue), var(--blue2), #80d0f0); }
.acc-orange { background: linear-gradient(90deg, var(--orange), var(--orange2), #ffd080); }
.acc-red    { background: linear-gradient(90deg, var(--red), #f07070, #ffaaaa); }
.acc-purple { background: linear-gradient(90deg, var(--purple), #a070e0, #c8a8ff); }

/* Icon badge */
.card-icon {
  width: 44px; height: 44px; border-radius: 14px;
  display: grid; place-items: center; font-size: 1.4rem;
  margin-bottom: 14px; flex-shrink: 0;
}
.icon-green  { background: var(--greenlt); }
.icon-blue   { background: var(--bluelt); }
.icon-orange { background: var(--orangelt); }
.icon-red    { background: var(--redlt); }
.icon-purple { background: var(--purplelt); }

.clabel {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.58rem; text-transform: uppercase; letter-spacing: 0.12em;
  color: var(--text3); margin-bottom: 6px;
  display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
}

.pin {
  background: var(--bg3); border: 1px solid var(--border);
  padding: 2px 7px; border-radius: 5px;
  font-size: 0.5rem; color: var(--text3);
}

.metric-val {
  font-size: 3.2rem; font-weight: 900; line-height: 1;
  letter-spacing: -0.03em; margin-bottom: 2px;
}
.metric-val .u {
  font-size: 1.1rem; font-weight: 700;
  color: var(--text3); margin-left: 3px;
  font-family: 'JetBrains Mono', monospace;
}

.metric-sub {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem; color: var(--text3); margin-bottom: 14px;
}

/* Chips */
.chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 12px; border-radius: 8px;
  font-size: 0.72rem; font-weight: 800;
  letter-spacing: 0.02em;
}
.chip-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

.c-green  { background: var(--greenlt);  color: var(--green);  border: 1px solid rgba(45,158,95,0.25); }
.c-green  .chip-dot { background: var(--green2); }
.c-blue   { background: var(--bluelt);   color: var(--blue);   border: 1px solid rgba(26,143,196,0.25); }
.c-blue   .chip-dot { background: var(--blue2); }
.c-orange { background: var(--orangelt); color: var(--orange); border: 1px solid rgba(224,120,32,0.25); }
.c-orange .chip-dot { background: var(--orange2); }
.c-red    { background: var(--redlt);    color: var(--red);    border: 1px solid rgba(224,61,61,0.25); }
.c-red    .chip-dot { background: var(--red); }
.c-gray   { background: var(--bg3);      color: var(--text3);  border: 1px solid var(--border); }
.c-gray   .chip-dot { background: var(--text4); }

/* Progress bar */
.bar-wrap {
  height: 8px; background: var(--bg3);
  border-radius: 100px; overflow: hidden; margin-top: 10px;
}
.bar-fill {
  height: 100%; border-radius: 100px;
  transition: width 1s cubic-bezier(0.4,0,0.2,1);
}
.bf-green  { background: linear-gradient(90deg, var(--green), var(--green2), var(--green3)); }
.bf-blue   { background: linear-gradient(90deg, var(--blue), var(--blue2)); }
.bf-orange { background: linear-gradient(90deg, var(--orange), var(--orange2)); }
.bf-red    { background: linear-gradient(90deg, var(--red), #f07070); }

.bar-legend {
  display: flex; justify-content: space-between;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.52rem; color: var(--text4); margin-top: 5px;
}

/* ════════════════ DASHBOARD GRID ════════════════ */
.dash-layout {
  display: grid;
  grid-template-columns: 1fr 1fr 300px;
  gap: 14px;
}
@media(max-width:1100px){ .dash-layout{ grid-template-columns: 1fr 1fr; } .dash-sidebar{ grid-column:1/-1; } }
@media(max-width:680px) { .dash-layout{ grid-template-columns: 1fr; } }

/* ── SOIL 1 — Ring gauge ── */
.soil1-inner { display: flex; align-items: center; gap: 18px; }
.ring-gauge-wrap { position: relative; flex-shrink: 0; }
.ring-gauge-wrap svg { display: block; }
.ring-center {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%, -50%); text-align: center;
}
.ring-center-val {
  font-size: 1.5rem; font-weight: 900;
  color: var(--orange); line-height: 1;
}
.ring-center-lbl {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.48rem; color: var(--text3);
}

/* ── SOIL 2 ── */
.soil2-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.soil2-state  { font-size: 2.2rem; font-weight: 900; }
.soil2-chart-wrap { position: relative; height: 110px; margin-top: 6px; }

/* ── TEMP + HUM ── */
.th-split { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 6px; }

.therm-wrap { display: flex; gap: 10px; align-items: flex-end; margin-top: 8px; }
.therm-outer { display: flex; flex-direction: column; align-items: center; gap: 3px; flex-shrink: 0; }
.therm-tube {
  width: 18px; height: 88px;
  background: var(--bg3); border-radius: 9px 9px 0 0;
  border: 1.5px solid var(--border); position: relative; overflow: hidden;
}
.therm-fill {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: linear-gradient(180deg, var(--orange2), var(--orange), #c04000);
  transition: height 1.2s cubic-bezier(0.4,0,0.2,1);
}
.therm-bulb {
  width: 24px; height: 24px; border-radius: 50%;
  background: linear-gradient(135deg, var(--orange2), var(--orange));
  border: 1.5px solid var(--border); margin-top: -2px;
  box-shadow: 0 3px 12px rgba(224,120,32,0.4);
}
.therm-scale {
  display: flex; flex-direction: column;
  justify-content: space-between; height: 88px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.44rem; color: var(--text4); padding: 2px 0;
}
.temp-readout { flex: 1; }
.th-val { font-size: 2.2rem; font-weight: 900; line-height: 1; margin-bottom: 4px; }
.th-val .u { font-size: 0.9rem; font-family: 'JetBrains Mono', monospace; color: var(--text3); margin-left: 2px; }
.th-lbl { font-family: 'JetBrains Mono', monospace; font-size: 0.56rem; color: var(--text3); margin-bottom: 8px; }

.hum-gauge-wrap { display: flex; flex-direction: column; align-items: center; margin-top: 4px; }
.hum-gauge-svg { display: block; overflow: visible; }
.hum-readout { margin-top: -10px; text-align: center; }

/* ── SIDEBAR ── */
.dash-sidebar { grid-column: 3; grid-row: 1 / 3; display: flex; flex-direction: column; gap: 14px; }

/* Tank */
.tank-visual { display: flex; align-items: flex-end; justify-content: center; gap: 18px; margin: 14px 0 10px; }
.tank-vessel-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.tank-body {
  width: 54px; height: 110px;
  border: 2.5px solid var(--border2); border-radius: 12px 12px 16px 16px;
  background: var(--bg3); overflow: hidden; position: relative;
}
.tank-fill {
  position: absolute; bottom: 0; left: 0; right: 0;
  background: linear-gradient(180deg, rgba(53,170,223,0.55) 0%, rgba(26,143,196,0.9) 100%);
  transition: height 1.3s cubic-bezier(0.4,0,0.2,1);
}
.tank-fill::before {
  content: ''; position: absolute;
  top: -6px; left: -4px; right: -4px; height: 12px;
  background: rgba(53,170,223,0.35); border-radius: 50%;
  animation: tankwave 3s ease-in-out infinite;
}
@keyframes tankwave { 0%,100%{transform:translateX(0) scaleX(1)}50%{transform:translateX(3px) scaleX(1.1)} }
.tank-body::before,.tank-body::after {
  content:''; position:absolute; left:5px; width:9px; height:1.5px;
  background:var(--border); z-index:2;
}
.tank-body::before{top:33%} .tank-body::after{top:66%}
.tank-cm {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.55rem; color: var(--text3); font-weight: 500;
}
.tank-stats { flex: 1; }
.tank-big { font-size: 2.5rem; font-weight: 900; color: var(--blue); line-height: 1; margin-bottom: 3px; }
.tank-big .u { font-size: 1rem; font-family: 'JetBrains Mono', monospace; color: var(--text3); margin-left: 2px; }

/* Pump */
.pump-body { display: flex; align-items: center; gap: 16px; margin-top: 8px; }
.pump-orb {
  width: 70px; height: 70px; border-radius: 50%;
  background: var(--bg3); border: 2.5px solid var(--border);
  display: grid; place-items: center; font-size: 1.9rem;
  flex-shrink: 0; transition: all 0.5s;
}
.pump-orb.running {
  border-color: var(--green2);
  background: var(--greenlt);
  box-shadow: 0 0 0 6px rgba(61,191,116,0.15), 0 0 30px rgba(61,191,116,0.3);
  animation: pumporb 2.8s ease-in-out infinite;
}
@keyframes pumporb {
  0%,100%{box-shadow:0 0 0 6px rgba(61,191,116,0.15),0 0 30px rgba(61,191,116,0.25)}
  50%{box-shadow:0 0 0 12px rgba(61,191,116,0.1),0 0 50px rgba(61,191,116,0.4)}
}
.pump-label { font-size: 2.2rem; font-weight: 900; line-height: 1; }

/* ── Mini trend chart ── */
.chart-tabs { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
.chart-tab {
  padding: 5px 12px; border: 1.5px solid var(--border);
  border-radius: 8px; background: transparent;
  color: var(--text3); font-family: 'Nunito', sans-serif;
  font-size: 0.72rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.chart-tab.active { background: var(--green); color: #fff; border-color: var(--green); }
.chart-container { position: relative; height: 132px; }

/* ── History charts ── */
.history-row { margin-top: 14px; }
.history-card {
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: var(--r); padding: 22px;
  box-shadow: var(--shadow);
}
.history-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px; gap: 12px; flex-wrap: wrap;
}
.history-title { font-size: 1.4rem; font-weight: 900; color: var(--text); }
.history-charts { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
@media(max-width:800px){ .history-charts{ grid-template-columns: 1fr; } }
.hchart-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.58rem; color: var(--text3);
  letter-spacing: 0.09em; text-transform: uppercase; margin-bottom: 8px;
}
.hchart-area { position: relative; height: 100px; }

/* ── Day chart ── */
.daychart-section { margin-top: 14px; }
.daychart-card {
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: var(--r); padding: 22px; box-shadow: var(--shadow);
}
.daychart-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 18px; gap: 12px; flex-wrap: wrap;
}
.daychart-title { font-size: 1.4rem; font-weight: 900; color: var(--text); }
.daychart-controls { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.daychart-sensor-tabs { display: flex; gap: 5px; }
.day-tab {
  padding: 5px 12px; border: 1.5px solid var(--border);
  border-radius: 8px; background: transparent;
  color: var(--text3); font-family: 'Nunito', sans-serif;
  font-size: 0.72rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
}
.day-tab.active { background: var(--green); color: #fff; border-color: var(--green); }
.date-picker-wrap { display: flex; align-items: center; gap: 8px; }
.date-nav-btn {
  width: 30px; height: 30px; border: 1.5px solid var(--border);
  border-radius: 9px; background: var(--bg3); color: var(--text2);
  font-size: 1rem; cursor: pointer; display: grid; place-items: center;
  transition: all 0.2s; font-weight: 800;
}
.date-nav-btn:hover { background: var(--greenlt); border-color: var(--green2); color: var(--green); }
.date-label {
  font-family: 'JetBrains Mono', monospace; font-size: 0.62rem;
  color: var(--text2); min-width: 88px; text-align: center; font-weight: 500;
}

.daychart-outer { display: flex; align-items: center; gap: 14px; }
.daychart-icon-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.daychart-icon { font-size: 1.7rem; }
.daychart-icon-lbl { font-family: 'JetBrains Mono', monospace; font-size: 0.44rem; color: var(--text3); letter-spacing: 0.08em; }
.daychart-area { flex: 1; position: relative; height: 200px; }

.daynight-legend { display: flex; gap: 20px; margin-top: 14px; justify-content: center; flex-wrap: wrap; }
.dn-leg-item { display: flex; align-items: center; gap: 7px; font-size: 0.72rem; font-weight: 600; color: var(--text3); }
.dn-leg-dot { width: 12px; height: 12px; border-radius: 4px; }

/* ════════════════ LOG PANEL ════════════════ */
.log-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 12px; flex-wrap: wrap; }
.log-heading { font-size: 1.8rem; font-weight: 900; color: var(--text); }
.log-controls { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.log-tag {
  font-family: 'JetBrains Mono', monospace; font-size: 0.58rem;
  color: var(--text3); background: var(--bg3);
  border: 1.5px solid var(--border); border-radius: 8px; padding: 4px 12px;
}
.log-scroll {
  background: var(--surface); border: 1.5px solid var(--border);
  border-radius: var(--r); overflow: hidden; overflow-x: auto;
  box-shadow: var(--shadow);
}
.log-tbl { width: 100%; min-width: 760px; border-collapse: collapse; font-family: 'JetBrains Mono', monospace; font-size: 0.68rem; }
.log-tbl thead th {
  padding: 13px 16px; text-align: left;
  font-size: 0.56rem; text-transform: uppercase; letter-spacing: 0.09em;
  color: var(--text3); background: var(--bg3);
  border-bottom: 1.5px solid var(--border); white-space: nowrap; font-weight: 700;
}
.log-tbl tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
.log-tbl tbody tr:last-child { border-bottom: none; }
.log-tbl tbody tr:hover { background: var(--bg3); }
.log-tbl tbody tr:first-child { background: var(--greenlt); }
.log-tbl tbody td { padding: 11px 16px; color: var(--text2); white-space: nowrap; vertical-align: middle; }

.dn-cell { display: flex; align-items: center; gap: 8px; }
.dn-icon-wrap {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--bg3); display: grid; place-items: center; font-size: 0.85rem; flex-shrink: 0;
}
.dn-label-txt { font-size: 0.56rem; color: var(--text3); }

.v-g  { color: var(--green); font-weight: 700; }
.v-b  { color: var(--blue); font-weight: 700; }
.v-o  { color: var(--orange); font-weight: 700; }
.v-r  { color: var(--red); font-weight: 700; }
.v-x  { color: var(--text3); }

.pump-ind { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 7px; font-size: 0.62rem; font-weight: 800; }
.pump-ind.on  { background: var(--greenlt); color: var(--green); border: 1px solid rgba(45,158,95,0.25); }
.pump-ind.off { background: var(--bg3); color: var(--text3); border: 1px solid var(--border); }
.pdot { width: 6px; height: 6px; border-radius: 50%; }
.pdot.on  { background: var(--green2); }
.pdot.off { background: var(--text4); }

.latest-dot {
  display: inline-block; width: 8px; height: 8px; border-radius: 50%;
  background: var(--green2); margin-right: 6px;
  animation: livepulse 2s ease-in-out infinite;
}

.empty-state { padding: 60px 20px; text-align: center; color: var(--text3); font-size: 0.75rem; }
.empty-icon { font-size: 3rem; margin-bottom: 12px; }

/* ════════════════ FOOTER ════════════════ */
footer {
  text-align: center; padding: 36px 0 16px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.58rem; color: var(--text4);
  border-top: 1.5px solid var(--border); margin-top: 60px;
}
footer span { color: var(--green); font-weight: 700; }

/* Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg3); }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--green3); }

@media(max-width:480px) {
  header {
    padding: 0 12px;
    height: auto;
    flex-wrap: wrap;
    padding: 10px 12px;
    gap: 8px;
  }

  .brand-name { font-size: 1rem; }
  .brand-logo { width: 34px; height: 34px; font-size: 1.1rem; }

  .nav-tab { padding: 6px 12px; font-size: 0.72rem; }

  .live-badge { font-size: 0.55rem; padding: 5px 10px; }

  .dn-filter-btn { font-size: 0.65rem; padding: 5px 10px; }

  .hero-title { font-size: 1.8rem; }

  .metric-val { font-size: 2.2rem; }

  .wrap { padding: 16px 12px 60px; }

  .card { padding: 16px; }

  .th-split { grid-template-columns: 1fr; }

  .tank-big { font-size: 1.8rem; }

  .pump-label { font-size: 1.6rem; }
}

</style>
</head>
<body>

<header>
  <div class="brand">
    <div class="brand-logo">🌱</div>
    <div>
      <div class="brand-name">Shiggy's Irrigation</div>
      <div class="brand-sub">Wemos D1 R1 · ESP8266</div>
    </div>
  </div>
  <div class="header-right">
    <div class="nav-tabs">
      <button class="nav-tab active" onclick="switchTab('dashboard',this)">Dashboard</button>
      <button class="nav-tab" onclick="switchTab('log',this)">Time Log</button>
    </div>
    <button class="dn-filter-btn all-active" id="dn-filter-btn" onclick="cycleDnFilter()">
      <span id="dn-filter-icon">🌿</span>
      <span id="dn-filter-label">All Hours</span>
    </button>
    <div class="live-badge"><span class="live-dot"></span>LIVE · 5s</div>
  </div>
</header>

<div class="wrap">

  <div class="page-hero">
    <div>
      <div class="hero-eyebrow">irrigation_db · sensor_readings</div>
      <div class="hero-title"><span id="dn-phase" class="accent">Field</span> Status</div>
    </div>
    <div class="hero-right">
      <div class="ts-block">Last reading<br><span class="ts-val" id="ts">—</span></div>
      <button class="export-btn" onclick="exportCSV()">⬇ Export</button>
    </div>
  </div>

  <!-- DASHBOARD PANEL -->
  <div class="panel active" id="panel-dashboard">
    <div class="dash-layout">

      <!-- SOIL 1 -->
      <div class="card">
        <div class="acc acc-orange"></div>
        <div class="clabel">Soil Moisture · Analog <span class="pin">A0</span></div>
        <div class="soil1-inner">
          <div class="ring-gauge-wrap">
            <svg width="110" height="110" viewBox="0 0 110 110">
              <circle cx="55" cy="55" r="44" fill="none" stroke="#f0f7ee" stroke-width="11"/>
              <circle id="ring-track" cx="55" cy="55" r="44" fill="none"
                stroke="url(#rgGrad)" stroke-width="11" stroke-linecap="round"
                stroke-dasharray="276.46" stroke-dashoffset="276.46"
                transform="rotate(-90 55 55)"
                style="transition:stroke-dashoffset 1s cubic-bezier(0.4,0,0.2,1)"/>
              <defs>
                <linearGradient id="rgGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#e07820"/>
                  <stop offset="100%" stop-color="#ffd080"/>
                </linearGradient>
              </defs>
            </svg>
            <div class="ring-center">
              <div class="ring-center-val" id="s1-ring-val">0%</div>
              <div class="ring-center-lbl">moisture</div>
            </div>
          </div>
          <div style="flex:1">
            <div class="metric-val" id="s1-val" style="color:var(--orange)">0<span class="u">%</span></div>
            <div class="metric-sub">soil1_moisture</div>
            <div class="chip c-gray" id="s1-chip"><span class="chip-dot"></span>—</div>
          </div>
        </div>
        <div class="bar-legend"><span>DRY 0%</span><span>100% WET</span></div>
        <div class="bar-wrap"><div class="bar-fill bf-orange" id="s1-bar" style="width:0%"></div></div>
      </div>

      <!-- SOIL 2 -->
      <div class="card">
        <div class="acc acc-green"></div>
        <div class="clabel">Soil Moisture · Digital <span class="pin">D5</span></div>
        <div class="soil2-header">
          <div>
            <div class="soil2-state" id="s2-word" style="color:var(--green)">—</div>
            <div class="metric-sub" style="margin-bottom:4px">soil2_wet · 1=DRY · 0=WET</div>
          </div>
          <div class="chip c-gray" id="s2-chip"><span class="chip-dot"></span>—</div>
        </div>
        <div class="soil2-chart-wrap"><canvas id="soil2-line-chart"></canvas></div>
      </div>

      <!-- SIDEBAR -->
      <div class="dash-sidebar">
        <!-- Water tank -->
        <div class="card">
          <div class="acc acc-blue"></div>
          <div class="clabel">Water Tank · HC-SR04 <span class="pin">D6</span><span class="pin">D7</span></div>
          <div class="tank-visual">
            <div class="tank-vessel-wrap">
              <div class="tank-body"><div class="tank-fill" id="tank-fill" style="height:0%"></div></div>
              <div class="tank-cm" id="tank-cm">0.0 cm</div>
            </div>
            <div class="tank-stats">
              <div class="tank-big" id="wl-val">0.0<span class="u">%</span></div>
              <div class="metric-sub">max 20 cm</div>
              <div class="chip c-gray" id="wl-chip"><span class="chip-dot"></span>—</div>
            </div>
          </div>
          <div class="bar-wrap"><div class="bar-fill bf-blue" id="wl-bar" style="width:0%"></div></div>
        </div>

        <!-- Pump -->
        <div class="card">
          <div class="acc acc-green"></div>
          <div class="clabel">Water Pump · Relay <span class="pin">D1 · GPIO5</span></div>
          <div class="pump-body">
            <div class="pump-orb off" id="pump-orb">💧</div>
            <div>
              <div class="pump-label" id="pump-label" style="color:var(--text3)">IDLE</div>
              <div class="metric-sub" id="pump-desc" style="margin-bottom:10px">Relay inactive · standby</div>
              <div class="chip c-gray" id="pump-chip"><span class="chip-dot"></span>OFF</div>
            </div>
          </div>
        </div>
      </div>

      <!-- TEMP & HUM -->
      <div class="card">
        <div class="acc acc-orange"></div>
        <div class="clabel">Temperature &amp; Humidity <span class="pin">DHT11 · D2</span></div>
        <div class="th-split">
          <div>
            <div class="th-lbl">Temperature</div>
            <div class="therm-wrap">
              <div class="therm-outer">
                <div class="therm-tube"><div class="therm-fill" id="therm-fill" style="height:0%"></div></div>
                <div class="therm-bulb"></div>
              </div>
              <div class="therm-scale"><span>50°</span><span>38°</span><span>25°</span><span>12°</span><span>0°</span></div>
              <div class="temp-readout">
                <div class="th-val" id="temp-val" style="color:var(--orange)">0<span class="u">°C</span></div>
                <div id="temp-chip" class="chip c-gray" style="margin-top:6px"><span class="chip-dot"></span>—</div>
              </div>
            </div>
          </div>
          <div>
            <div class="th-lbl">Humidity</div>
            <div class="hum-gauge-wrap">
              <svg class="hum-gauge-svg" width="130" height="80" viewBox="0 0 130 80">
                <defs>
                  <linearGradient id="humGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#1a8fc4"/>
                    <stop offset="100%" stop-color="#80d0f0"/>
                  </linearGradient>
                </defs>
                <path d="M 15 72 A 50 50 0 0 1 115 72" fill="none" stroke="#e8f4e5" stroke-width="10" stroke-linecap="round"/>
                <path id="hum-arc" d="M 15 72 A 50 50 0 0 1 115 72" fill="none" stroke="url(#humGrad)" stroke-width="10" stroke-linecap="round" stroke-dasharray="157.08" stroke-dashoffset="157.08" style="transition:stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1)"/>
                <line id="hum-needle" x1="65" y1="72" x2="65" y2="28" stroke="#1a8fc4" stroke-width="2.5" stroke-linecap="round" style="transform-origin:65px 72px;transform:rotate(0deg);transition:transform 1.2s cubic-bezier(0.4,0,0.2,1)"/>
                <circle cx="65" cy="72" r="5" fill="#1a8fc4"/>
                <text x="11" y="80" fill="#9ab899" font-family="JetBrains Mono,monospace" font-size="8">0%</text>
                <text x="105" y="80" fill="#9ab899" font-family="JetBrains Mono,monospace" font-size="8">100%</text>
                <text x="57" y="18" fill="#9ab899" font-family="JetBrains Mono,monospace" font-size="8">50%</text>
              </svg>
              <div class="hum-readout">
                <div class="th-val" id="hum-val" style="color:var(--blue);text-align:center">0<span class="u">%</span></div>
                <div id="hum-chip" class="chip c-gray" style="margin-top:4px"><span class="chip-dot"></span>—</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MINI TREND -->
      <div class="card">
        <div class="acc acc-green"></div>
        <div class="clabel">Sensor Trend</div>
        <div class="chart-tabs">
          <button class="chart-tab active" onclick="setMiniChart('soil1',this)">Soil 1</button>
          <button class="chart-tab" onclick="setMiniChart('temp',this)">Temp</button>
          <button class="chart-tab" onclick="setMiniChart('hum',this)">Humidity</button>
          <button class="chart-tab" onclick="setMiniChart('water',this)">Water</button>
        </div>
        <div class="chart-container"><canvas id="mini-chart"></canvas></div>
      </div>

    </div><!-- /dash-layout -->

    <!-- HISTORY -->
    <div class="history-row">
      <div class="history-card">
        <div class="history-header">
          <div class="history-title">📈 Sensor History</div>
          <div class="chip c-gray" id="hist-count"><span class="chip-dot"></span>— readings</div>
        </div>
        <div class="history-charts">
          <div>
            <div class="hchart-label" style="color:var(--orange)">⬤ Soil Moisture (%)</div>
            <div class="hchart-area"><canvas id="chart-soil1"></canvas></div>
          </div>
          <div>
            <div class="hchart-label">⬤ <span style="color:var(--orange)">Temp (°C)</span> &amp; <span style="color:var(--blue)">Humidity (%)</span></div>
            <div class="hchart-area"><canvas id="chart-temphum"></canvas></div>
          </div>
          <div>
            <div class="hchart-label" style="color:var(--blue)">⬤ Water Level (cm)</div>
            <div class="hchart-area"><canvas id="chart-water"></canvas></div>
          </div>
        </div>
      </div>
    </div>

    <!-- DAY CHART -->
    <div class="daychart-section">
      <div class="daychart-card">
        <div class="daychart-header">
          <div class="daychart-title">🗓 Daily Timeline</div>
          <div class="daychart-controls">
            <div class="date-picker-wrap">
              <button class="date-nav-btn" onclick="shiftDay(-1)">‹</button>
              <span class="date-label" id="day-label">Today</span>
              <button class="date-nav-btn" onclick="shiftDay(1)">›</button>
            </div>
            <div class="daychart-sensor-tabs">
              <button class="day-tab active" onclick="setDayChart('soil1',this)">Soil 1</button>
              <button class="day-tab" onclick="setDayChart('temp',this)">Temp</button>
              <button class="day-tab" onclick="setDayChart('hum',this)">Humidity</button>
              <button class="day-tab" onclick="setDayChart('water',this)">Water</button>
            </div>
          </div>
        </div>
        <div class="daychart-outer">
          <div class="daychart-icon-wrap">
            <span class="daychart-icon">☀️</span>
            <span class="daychart-icon-lbl">DAY</span>
          </div>
          <div class="daychart-area"><canvas id="day-chart"></canvas></div>
          <div class="daychart-icon-wrap">
            <span class="daychart-icon">🌙</span>
            <span class="daychart-icon-lbl">NIGHT</span>
          </div>
        </div>
        <div class="daynight-legend">
          <div class="dn-leg-item"><div class="dn-leg-dot" style="background:rgba(255,200,60,0.35)"></div><span>Daytime (6 AM – 6 PM)</span></div>
          <div class="dn-leg-item"><div class="dn-leg-dot" style="background:rgba(26,143,196,0.2)"></div><span>Nighttime (6 PM – 6 AM)</span></div>
          <div class="dn-leg-item" id="filter-hint" style="display:none"><div class="dn-leg-dot" style="background:rgba(45,158,95,0.4)"></div><span id="filter-hint-txt"></span></div>
        </div>
      </div>
    </div>

  </div><!-- /panel-dashboard -->

  <!-- LOG PANEL -->
  <div class="panel" id="panel-log">
    <div class="log-top">
      <div class="log-heading">Reading History</div>
      <div class="log-controls">
        <span class="log-tag" id="log-count">— entries</span>
        <button class="export-btn" onclick="exportCSV()">⬇ Export CSV</button>
      </div>
    </div>
    <div class="log-scroll">
      <table class="log-tbl">
        <thead>
          <tr>
            <th>Timestamp</th><th>Time of Day</th>
            <th>🌱 Soil 1 (%)</th><th>💧 Soil 2</th>
            <th>🌡 Temp (°C)</th><th>💦 Humidity (%)</th>
            <th>🪣 Water (cm)</th><th>⚡ Pump</th>
          </tr>
        </thead>
        <tbody id="log-body">
          <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📡</div>Loading…</div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /wrap -->

<footer>Who the hell is Shiggy? · <span>PHP + MySQL</span> · Wemos D1 R1 · ESP8266</footer>

<script>
const TANK_MAX = 20.0;
const CIRC = 2 * Math.PI * 44;
const HUM_ARC_LEN = 157.08;

Chart.defaults.color = '#6b8f68';
Chart.defaults.font.family = "'JetBrains Mono', monospace";
Chart.defaults.font.size = 10;

const makeOpts = (color, yLbl='', min=null, max=null) => ({
  responsive:true, maintainAspectRatio:false, animation:{duration:600},
  plugins:{
    legend:{display:false},
    tooltip:{
      backgroundColor:'#ffffff', borderColor:'#d4e8cf', borderWidth:1.5,
      titleColor:'#3d5c3a', bodyColor:color, padding:10, cornerRadius:10,
      boxShadow:'0 4px 12px rgba(45,158,95,0.15)',
      callbacks:{label:ctx=>` ${ctx.parsed.y.toFixed(1)}${yLbl}`}
    }
  },
  scales:{
    x:{display:true, ticks:{maxTicksLimit:6,color:'#9ab899'}, grid:{color:'rgba(45,158,95,0.08)'}, border:{display:false}},
    y:{display:true, min, max, ticks:{maxTicksLimit:4,color:'#9ab899'}, grid:{color:'rgba(45,158,95,0.08)'}, border:{display:false}}
  }
});

let soil1Chart, temphumChart, waterChart, miniChart, soil2LineChart, dayChart;
let miniMode = 'soil1', dayMode = 'soil1';
let historyData = [];
let dayOffset = 0;
let dnFilter = 'all';

function cycleDnFilter() {
  const cycle = {all:'day',day:'night',night:'all'};
  dnFilter = cycle[dnFilter];
  const btn=document.getElementById('dn-filter-btn');
  const icon=document.getElementById('dn-filter-icon');
  const lbl=document.getElementById('dn-filter-label');
  const hint=document.getElementById('filter-hint');
  const htxt=document.getElementById('filter-hint-txt');
  btn.className='dn-filter-btn';
  if(dnFilter==='day')   {btn.classList.add('day-active');   icon.textContent='☀️'; lbl.textContent='Day Only';   hint.style.display='flex'; htxt.textContent='Filtered: Daytime only (6 AM – 6 PM)';}
  else if(dnFilter==='night'){btn.classList.add('night-active');icon.textContent='🌙'; lbl.textContent='Night Only'; hint.style.display='flex'; htxt.textContent='Filtered: Nighttime only (6 PM – 6 AM)';}
  else                   {btn.classList.add('all-active');   icon.textContent='🌿'; lbl.textContent='All Hours';  hint.style.display='none';}
  if(historyData.length) buildDayChart();
}

function isDaytime(s){const h=new Date(s).getHours();return h>=6&&h<18;}

function initCharts() {
  const mkGrad=(ctx,c1,c2)=>{const g=ctx.createLinearGradient(0,0,0,100);g.addColorStop(0,c1);g.addColorStop(1,c2);return g;};

  const c1=document.getElementById('chart-soil1').getContext('2d');
  soil1Chart=new Chart(c1,{type:'line',data:{labels:[],datasets:[{data:[],borderColor:'#e07820',backgroundColor:mkGrad(c1,'rgba(224,120,32,0.2)','rgba(224,120,32,0.02)'),fill:true,tension:0.4,pointRadius:2,pointBackgroundColor:'#f59d45',borderWidth:2}]},options:makeOpts('#e07820','%',0,100)});

  const c2=document.getElementById('chart-temphum').getContext('2d');
  temphumChart=new Chart(c2,{type:'line',data:{labels:[],datasets:[{label:'Temp',data:[],borderColor:'#e07820',backgroundColor:'rgba(224,120,32,0.1)',fill:true,tension:0.4,pointRadius:2,pointBackgroundColor:'#e07820',borderWidth:2},{label:'Hum',data:[],borderColor:'#1a8fc4',backgroundColor:'rgba(26,143,196,0.1)',fill:true,tension:0.4,pointRadius:2,pointBackgroundColor:'#1a8fc4',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:600},plugins:{legend:{display:true,labels:{color:'#6b8f68',boxWidth:10,padding:10,font:{size:10}}},tooltip:{backgroundColor:'#fff',borderColor:'#d4e8cf',borderWidth:1.5,padding:10,cornerRadius:10}},scales:{x:{display:true,ticks:{maxTicksLimit:6,color:'#9ab899'},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}},y:{display:true,ticks:{maxTicksLimit:4,color:'#9ab899'},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}}}}});

  const c3=document.getElementById('chart-water').getContext('2d');
  waterChart=new Chart(c3,{type:'line',data:{labels:[],datasets:[{data:[],borderColor:'#1a8fc4',backgroundColor:mkGrad(c3,'rgba(26,143,196,0.18)','rgba(26,143,196,0.02)'),fill:true,tension:0.4,pointRadius:2,pointBackgroundColor:'#35aadf',borderWidth:2}]},options:makeOpts('#1a8fc4',' cm',0,20)});

  const cm=document.getElementById('mini-chart').getContext('2d');
  miniChart=new Chart(cm,{type:'line',data:{labels:[],datasets:[{data:[],borderColor:'#e07820',backgroundColor:mkGrad(cm,'rgba(224,120,32,0.18)','rgba(224,120,32,0.02)'),fill:true,tension:0.4,pointRadius:0,borderWidth:2.5}]},options:makeOpts('#e07820','%',0,100)});

  const cs2=document.getElementById('soil2-line-chart').getContext('2d');
  soil2LineChart=new Chart(cs2,{type:'line',data:{labels:[],datasets:[{data:[],borderColor:'#2d9e5f',backgroundColor:'rgba(45,158,95,0.12)',fill:true,tension:0.35,pointRadius:3,pointBackgroundColor:'#3dbf74',borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,animation:{duration:800},plugins:{legend:{display:false},tooltip:{backgroundColor:'#fff',borderColor:'#d4e8cf',borderWidth:1.5,titleColor:'#3d5c3a',padding:10,cornerRadius:10,callbacks:{label:ctx=>ctx.parsed.y===1?' DRY':' WET'}}},scales:{x:{display:true,ticks:{maxTicksLimit:5,color:'#9ab899'},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}},y:{display:true,min:0,max:1.2,ticks:{maxTicksLimit:3,color:'#9ab899',callback:v=>v===0?'WET':v===1?'DRY':''},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}}}}});

  const dayNightPlugin={
    id:'dayNightShading',
    beforeDraw(chart){
      const{ctx,chartArea,scales}=chart;
      if(!chartArea||!scales.x) return;
      const labels=chart.data.labels;
      if(!labels||!labels.length) return;
      const{top,bottom}=chartArea;
      labels.forEach((lbl,i)=>{
        const h=parseInt(lbl.split(':')[0]);
        const isDay=h>=6&&h<18;
        const x0=scales.x.getPixelForValue(i);
        const x1=i<labels.length-1?scales.x.getPixelForValue(i+1):x0+1;
        ctx.save();
        ctx.fillStyle=isDay?'rgba(255,200,60,0.07)':'rgba(26,143,196,0.06)';
        ctx.fillRect(x0,top,x1-x0,bottom-top);
        ctx.restore();
      });
    }
  };

  const cd=document.getElementById('day-chart').getContext('2d');
  dayChart=new Chart(cd,{
    type:'line',
    data:{labels:[],datasets:[{data:[],borderColor:'#e07820',backgroundColor:'rgba(224,120,32,0.15)',fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#f59d45',pointBorderColor:'rgba(255,255,255,0.8)',pointBorderWidth:1.5,borderWidth:2.5,spanGaps:true}]},
    options:{
      responsive:true,maintainAspectRatio:false,animation:{duration:700},
      plugins:{
        legend:{display:false},
        tooltip:{backgroundColor:'#fff',borderColor:'#d4e8cf',borderWidth:1.5,titleColor:'#3d5c3a',bodyColor:'#e07820',padding:10,cornerRadius:10,callbacks:{title:items=>items[0].label+'h',label:ctx=>ctx.parsed.y!==null?` ${ctx.parsed.y.toFixed(1)}`:'No data'}},
        dayNightShading:{}
      },
      scales:{
        x:{display:true,ticks:{maxTicksLimit:13,color:'#9ab899',font:{size:9}},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}},
        y:{display:true,ticks:{maxTicksLimit:5,color:'#9ab899'},grid:{color:'rgba(45,158,95,0.08)'},border:{display:false}}
      }
    },
    plugins:[dayNightPlugin]
  });
}

function buildDayChart() {
  if(!historyData.length) return;
  const target=new Date(); target.setDate(target.getDate()+dayOffset);
  const yyyy=target.getFullYear(),mm=String(target.getMonth()+1).padStart(2,'0'),dd=String(target.getDate()).padStart(2,'0');
  const dateStr=`${yyyy}-${mm}-${dd}`;
  const lbl=document.getElementById('day-label');
  if(dayOffset===0) lbl.textContent='Today';
  else if(dayOffset===-1) lbl.textContent='Yesterday';
  else lbl.textContent=`${mm}/${dd}/${yyyy}`;
  let recs=[...historyData].filter(r=>r.created_at&&r.created_at.startsWith(dateStr)).reverse();
  if(dnFilter==='day') recs=recs.filter(r=>isDaytime(r.created_at));
  else if(dnFilter==='night') recs=recs.filter(r=>!isDaytime(r.created_at));
  const buckets={};
  for(let h=0;h<24;h++) buckets[String(h).padStart(2,'0')+':00']=[];
  recs.forEach(r=>{const h=String(new Date(r.created_at).getHours()).padStart(2,'0')+':00';buckets[h].push(r);});
  const labels=Object.keys(buckets);
  const avg=(arr,fn)=>arr.length?arr.reduce((s,r)=>s+fn(r),0)/arr.length:null;
  let data,color,bgColor,yLbl,yMin,yMax;
  if(dayMode==='soil1')     {data=labels.map(l=>avg(buckets[l],r=>parseFloat(r.soil1_moisture)||0));  color='#e07820';bgColor='rgba(224,120,32,0.15)';  yLbl='%';   yMin=0;    yMax=100;}
  else if(dayMode==='temp') {data=labels.map(l=>avg(buckets[l],r=>parseFloat(r.temperature)||0));     color='#e07820';bgColor='rgba(224,120,32,0.12)';  yLbl='°C'; yMin=null; yMax=null;}
  else if(dayMode==='hum')  {data=labels.map(l=>avg(buckets[l],r=>parseFloat(r.humidity)||0));        color='#1a8fc4';bgColor='rgba(26,143,196,0.15)'; yLbl='%';   yMin=0;    yMax=100;}
  else                      {data=labels.map(l=>avg(buckets[l],r=>parseFloat(r.water_level_cm)||0));  color='#1a8fc4';bgColor='rgba(26,143,196,0.14)'; yLbl=' cm'; yMin=0;    yMax=20;}
  dayChart.data.labels=labels; dayChart.data.datasets[0].data=data;
  dayChart.data.datasets[0].borderColor=color; dayChart.data.datasets[0].pointBackgroundColor=color;
  dayChart.data.datasets[0].backgroundColor=bgColor;
  dayChart.options.scales.y.min=yMin; dayChart.options.scales.y.max=yMax;
  dayChart.options.plugins.tooltip.bodyColor=color;
  dayChart.options.plugins.tooltip.callbacks.label=ctx=>ctx.parsed.y!==null?` ${ctx.parsed.y.toFixed(1)}${yLbl}`:'No data this hour';
  dayChart.update();
}

function setDayChart(mode,btn){dayMode=mode;document.querySelectorAll('.day-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');buildDayChart();}
function shiftDay(dir){dayOffset=Math.min(0,dayOffset+dir);buildDayChart();}

function updateSoil2Chart(logs){
  if(!logs||!logs.length) return;
  const rev=[...logs].reverse().slice(-20);
  const isDry=parseInt(rev[rev.length-1]?.soil2_wet)===1;
  const lc=isDry?'#e07820':'#2d9e5f';
  const bc=isDry?'rgba(224,120,32,0.15)':'rgba(45,158,95,0.12)';
  soil2LineChart.data.labels=rev.map(r=>fmtTime(r.created_at));
  soil2LineChart.data.datasets[0].data=rev.map(r=>parseInt(r.soil2_wet));
  soil2LineChart.data.datasets[0].borderColor=lc;
  soil2LineChart.data.datasets[0].pointBackgroundColor=lc;
  soil2LineChart.data.datasets[0].backgroundColor=bc;
  soil2LineChart.update();
}

function updateCharts(logs){
  if(!logs||!logs.length) return;
  const rev=[...logs].reverse();
  const lbl=rev.map(r=>fmtTime(r.created_at));
  soil1Chart.data.labels=lbl; soil1Chart.data.datasets[0].data=rev.map(r=>parseFloat(r.soil1_moisture)||0); soil1Chart.update('none');
  temphumChart.data.labels=lbl; temphumChart.data.datasets[0].data=rev.map(r=>parseFloat(r.temperature)||0); temphumChart.data.datasets[1].data=rev.map(r=>parseFloat(r.humidity)||0); temphumChart.update('none');
  waterChart.data.labels=lbl; waterChart.data.datasets[0].data=rev.map(r=>parseFloat(r.water_level_cm)||0); waterChart.update('none');
  updateMiniChart(rev,lbl); updateSoil2Chart(logs); buildDayChart();
  document.getElementById('hist-count').innerHTML=`<span class="chip-dot" style="background:var(--green2);width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px"></span>${logs.length} readings`;
}

function updateMiniChart(rev,lbl){
  let data,color,min,max;
  if(miniMode==='soil1')    {data=rev.map(r=>parseFloat(r.soil1_moisture)||0);  color='#e07820'; min=0;    max=100;}
  else if(miniMode==='temp'){data=rev.map(r=>parseFloat(r.temperature)||0);     color='#e07820'; min=null; max=null;}
  else if(miniMode==='hum') {data=rev.map(r=>parseFloat(r.humidity)||0);        color='#1a8fc4'; min=0;    max=100;}
  else                      {data=rev.map(r=>parseFloat(r.water_level_cm)||0);  color='#1a8fc4'; min=0;    max=20;}
  miniChart.data.labels=lbl; miniChart.data.datasets[0].data=data;
  miniChart.data.datasets[0].borderColor=color;
  miniChart.options.scales.y.min=min; miniChart.options.scales.y.max=max;
  miniChart.update();
}

function setMiniChart(mode,btn){
  miniMode=mode;
  document.querySelectorAll('.chart-tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  if(historyData.length){const rev=[...historyData].reverse();updateMiniChart(rev,rev.map(r=>fmtTime(r.created_at)));}
}

function updateThermometer(temp){
  document.getElementById('therm-fill').style.height=Math.min(Math.max((temp/50)*100,0),100)+'%';
  let cls,label;
  if(temp<15){cls='c-blue';label='COLD';}else if(temp<25){cls='c-green';label='COOL';}else if(temp<35){cls='c-orange';label='WARM';}else{cls='c-red';label='HOT';}
  setChip(document.getElementById('temp-chip'),cls,label);
}

function updateHumGauge(hum){
  const pct=Math.min(Math.max(hum,0),100)/100;
  document.getElementById('hum-arc').style.strokeDashoffset=HUM_ARC_LEN-(pct*HUM_ARC_LEN);
  document.getElementById('hum-needle').style.transform=`rotate(${-90+(pct*180)}deg)`;
  let cls,label;
  if(hum<30){cls='c-orange';label='DRY AIR';}else if(hum<60){cls='c-green';label='NORMAL';}else if(hum<80){cls='c-blue';label='HUMID';}else{cls='c-red';label='WET AIR';}
  setChip(document.getElementById('hum-chip'),cls,label);
}

function switchTab(tab,btn){
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach(b=>b.classList.remove('active'));
  document.getElementById('panel-'+tab).classList.add('active');
  btn.classList.add('active');
  if(tab==='log') fetchLog();
}

function getDayNight(s){
  if(!s) return{icon:'🌙',label:'Night',phase:'Night'};
  const h=new Date(s).getHours();
  if(h>=5&&h<9)   return{icon:'🌅',label:'Dawn',     phase:'Morning'};
  if(h>=9&&h<12)  return{icon:'☀️', label:'Morning',  phase:'Morning'};
  if(h>=12&&h<15) return{icon:'🌞',label:'Midday',   phase:'Afternoon'};
  if(h>=15&&h<18) return{icon:'🌤',label:'Afternoon',phase:'Afternoon'};
  if(h>=18&&h<21) return{icon:'🌇',label:'Evening',  phase:'Evening'};
  return              {icon:'🌙',label:'Night',    phase:'Night'};
}

function fmtDate(s){if(!s)return'—';return new Date(s).toLocaleString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});}
function fmtTime(s){if(!s)return'—';return new Date(s).toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit'});}
function setChip(el,cls,label){el.className='chip '+cls;el.innerHTML='<span class="chip-dot"></span>'+label;}

function exportCSV(){window.location.href='export_excel.php';}

async function fetchData(){
  try{
    const res=await fetch('?fetch=1'); if(!res.ok) return;
    const d=await res.json();
    document.getElementById('ts').textContent=d.created_at?fmtDate(d.created_at):'No data yet';
    const dn=getDayNight(d.created_at);
    document.getElementById('dn-phase').textContent=dn.phase;

    const s1=parseFloat(d.soil1_moisture)||0;
    document.getElementById('s1-val').innerHTML=s1.toFixed(1)+'<span class="u">%</span>';
    document.getElementById('s1-ring-val').textContent=s1.toFixed(0)+'%';
    document.getElementById('ring-track').style.strokeDashoffset=CIRC-(Math.min(s1,100)/100*CIRC);
    document.getElementById('s1-bar').style.width=Math.min(s1,100)+'%';
    const s1c=document.getElementById('s1-chip');
    if(s1>=60)setChip(s1c,'c-green','MOIST');else if(s1>=30)setChip(s1c,'c-orange','MEDIUM');else setChip(s1c,'c-orange','DRY');

    const s2d=parseInt(d.soil2_wet)===1;
    document.getElementById('s2-word').textContent=s2d?'DRY':'WET';
    document.getElementById('s2-word').style.color=s2d?'var(--orange)':'var(--green)';
    setChip(document.getElementById('s2-chip'),s2d?'c-orange':'c-green',s2d?'DRY':'WET');

    const temp=parseFloat(d.temperature)||0;
    document.getElementById('temp-val').innerHTML=temp.toFixed(1)+'<span class="u">°C</span>';
    updateThermometer(temp);

    const hum=parseFloat(d.humidity)||0;
    document.getElementById('hum-val').innerHTML=hum.toFixed(1)+'<span class="u">%</span>';
    updateHumGauge(hum);

    const wlCm=parseFloat(d.water_level_cm)||0;
    const wlPct=Math.min(Math.max((wlCm/TANK_MAX)*100,0),100);
    const empty=wlCm<=2.0;
    document.getElementById('wl-val').innerHTML=wlPct.toFixed(1)+'<span class="u">%</span>';
    document.getElementById('tank-fill').style.height=wlPct+'%';
    document.getElementById('tank-cm').textContent=wlCm.toFixed(1)+' cm';
    document.getElementById('wl-bar').style.width=wlPct+'%';
    const wlc=document.getElementById('wl-chip'),wlb=document.getElementById('wl-bar');
    if(empty){setChip(wlc,'c-red','EMPTY');wlb.className='bar-fill bf-red';}
    else if(wlPct>=75){setChip(wlc,'c-blue','FULL');wlb.className='bar-fill bf-blue';}
    else if(wlPct>=40){setChip(wlc,'c-blue','MEDIUM');wlb.className='bar-fill bf-blue';}
    else{setChip(wlc,'c-orange','LOW');wlb.className='bar-fill bf-orange';}

    const pOn=parseInt(d.pump_status)===1;
    document.getElementById('pump-label').textContent=pOn?'RUNNING':'IDLE';
    document.getElementById('pump-label').style.color=pOn?'var(--green)':'var(--text3)';
    document.getElementById('pump-desc').textContent=pOn?'Relay active · irrigating':'Relay inactive · standby';
    document.getElementById('pump-orb').className='pump-orb '+(pOn?'running':'off');
    document.getElementById('pump-orb').textContent=pOn?'⚡':'💧';
    setChip(document.getElementById('pump-chip'),pOn?'c-green':'c-gray',pOn?'ON':'OFF');
  }catch(e){console.error(e);}
}

async function fetchLog(){
  try{
    const res=await fetch('?log=1'); if(!res.ok) return;
    const logs=await res.json();
    historyData=logs; updateCharts(logs);
    document.getElementById('log-count').textContent=logs.length+' entries';
    const tbody=document.getElementById('log-body');
    if(!logs.length){tbody.innerHTML=`<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📡</div>No readings yet.</div></td></tr>`;return;}
    tbody.innerHTML=logs.map((r,i)=>{
      const dn=getDayNight(r.created_at);
      const s1=parseFloat(r.soil1_moisture)||0;
      const s2d=parseInt(r.soil2_wet)===1;
      const temp=parseFloat(r.temperature)||0;
      const hum=parseFloat(r.humidity)||0;
      const wlCm=parseFloat(r.water_level_cm)||0;
      const empty=wlCm<=2.0, pOn=parseInt(r.pump_status)===1;
      const s1c=s1>=60?'v-g':s1>=30?'v-o':'v-r';
      const wlc=empty?'v-r':wlCm>=15?'v-b':'v-o';
      const dot=i===0?'<span class="latest-dot"></span>':'';
      return`<tr>
        <td>${dot}${fmtDate(r.created_at)}</td>
        <td><div class="dn-cell"><div class="dn-icon-wrap">${dn.icon}</div><div><div style="color:var(--text2);font-size:0.65rem;font-weight:700">${dn.label}</div><div class="dn-label-txt">${fmtTime(r.created_at)}</div></div></div></td>
        <td class="${s1c}">${s1.toFixed(1)}%</td>
        <td class="${s2d?'v-o':'v-g'}">${s2d?'DRY':'WET'}</td>
        <td class="v-o">${temp.toFixed(1)}°C</td>
        <td class="v-b">${hum.toFixed(1)}%</td>
        <td class="${wlc}">${wlCm.toFixed(1)} cm</td>
        <td><span class="pump-ind ${pOn?'on':'off'}"><span class="pdot ${pOn?'on':'off'}"></span>${pOn?'ON':'OFF'}</span></td>
      </tr>`;
    }).join('');
  }catch(e){console.error(e);}
}

async function fetchHistory(){
  try{const res=await fetch('?log=1');if(!res.ok)return;const logs=await res.json();historyData=logs;updateCharts(logs);}catch(e){}
}

initCharts();
fetchData();
fetchHistory();
setInterval(fetchData,5000);
setInterval(fetchHistory,15000);
</script>
</body>
</html>