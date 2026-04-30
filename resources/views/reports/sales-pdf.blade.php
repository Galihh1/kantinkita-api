<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1a1a1a; }
  .header { background: #2D6A4F; color: white; padding: 20px; text-align: center; }
  .header h1 { font-size: 22px; font-weight: bold; }
  .header p { font-size: 11px; margin-top: 4px; opacity: 0.9; }
  .section { padding: 16px 20px; }
  .info-grid { display: table; width: 100%; margin-bottom: 16px; }
  .info-col { display: table-cell; width: 50%; vertical-align: top; }
  .info-col h3 { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
  .info-col p { font-size: 13px; font-weight: bold; }
  .stats-row { display: table; width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 8px; }
  .stat-box { display: table-cell; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px 16px; text-align: center; }
  .stat-box .label { font-size: 10px; color: #16a34a; text-transform: uppercase; }
  .stat-box .value { font-size: 18px; font-weight: bold; color: #15803d; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th { background: #2D6A4F; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
  td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
  tr:nth-child(even) td { background: #f9fafb; }
  .footer { background: #f3f4f6; padding: 12px 20px; text-align: center; font-size: 10px; color: #6b7280; margin-top: 20px; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }
  .badge-green { background: #dcfce7; color: #16a34a; }
  .divider { border: none; border-top: 2px solid #e5e7eb; margin: 16px 0; }
</style>
</head>
<body>
<div class="header">
  <h1>🍽️ KantinKita</h1>
  <p>Laporan Penjualan — {{ $tenant->tenant_name }}</p>
</div>

<div class="section">
  <div class="info-grid">
    <div class="info-col">
      <h3>Periode Laporan</h3>
      <p>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
    </div>
    <div class="info-col" style="text-align:right;">
      <h3>Dicetak</h3>
      <p>{{ now()->format('d M Y H:i') }}</p>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-box">
      <div class="label">Total Pendapatan</div>
      <div class="value">Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</div>
    </div>
    <div class="stat-box">
      <div class="label">Total Pesanan</div>
      <div class="value">{{ $report['total_orders'] }}</div>
    </div>
    <div class="stat-box">
      <div class="label">Rata-Rata Order</div>
      <div class="value">Rp {{ number_format($report['avg_order'], 0, ',', '.') }}</div>
    </div>
  </div>

  <hr class="divider">

  <h3 style="margin-bottom:8px; color:#2D6A4F; font-size:13px;">📊 Menu Terlaris</h3>
  <table>
    <thead>
      <tr>
        <th>No</th><th>Nama Menu</th><th>Terjual</th><th>Pendapatan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($report['top_menus'] as $i => $menu)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $menu->menu_name }}</td>
        <td>{{ $menu->total_qty }} porsi</td>
        <td>Rp {{ number_format($menu->total_revenue, 0, ',', '.') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <hr class="divider">

  <h3 style="margin-bottom:8px; color:#2D6A4F; font-size:13px;">📋 Detail Pesanan</h3>
  <table>
    <thead>
      <tr>
        <th>No. Order</th><th>Customer</th><th>Tanggal</th><th>Grand Total</th><th>Payment</th>
      </tr>
    </thead>
    <tbody>
      @foreach($report['orders'] as $order)
      <tr>
        <td>{{ $order->order_number }}</td>
        <td>{{ $order->user->full_name }}</td>
        <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
        <td>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
        <td>{{ $order->payment?->payment_type ?? '-' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="footer">
  KantinKita — Platform Kantin Digital | Dicetak oleh sistem pada {{ now()->format('d M Y H:i:s') }}
</div>
</body>
</html>
