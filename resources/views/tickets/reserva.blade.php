<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #000;
            width: 612px;
            margin: 0 auto;
        }
        .ticket {
            width: 288px;
            margin: 0 auto;
            padding: 10px 0;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #999; margin: 8px 0; }
        .logo { width: 120px; display: block; margin: 0 auto 5px; }
        .small { font-size: 9px; }
        .large { font-size: 14px; }
        .title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .line { margin-bottom: 2px; line-height: 1.6; }
        .ticket-footer { font-size: 9px; text-align: center; margin-top: 10px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            @if($logoData)
                <img src="{{ $logoData }}" class="logo" />
            @endif
            <div class="title">RV Park San Nicolás</div>
            <div class="small">Tel: 967 114 75 51</div>
            <div class="small">www.rvparksannicolas.com</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="title">TICKET DE RESERVA</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="line"><span class="bold">Folio:</span> #{{ str_pad($reservation->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="line"><span class="bold">Fecha:</span> {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="line"><span class="bold">Huésped:</span> {{ $reservation->guest_name }}</div>
            <div class="line"><span class="bold">Teléfono:</span> {{ $reservation->guest_phone }}</div>
            <div class="line"><span class="bold">Unidad:</span> {{ $reservation->unit->name }} ({{ ucfirst($reservation->unit->type) }})</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="line"><span class="bold">Check-in:</span> {{ \Carbon\Carbon::parse($reservation->check_in)->format('d/m/Y') }}{{ $reservation->check_in_time ? ' ' . substr($reservation->check_in_time, 0, 5) : '' }}</div>
            <div class="line"><span class="bold">Check-out:</span> {{ \Carbon\Carbon::parse($reservation->check_out)->format('d/m/Y') }}{{ $reservation->check_out_time ? ' ' . substr($reservation->check_out_time, 0, 5) : '' }}</div>
            <div class="line"><span class="bold">Noches:</span> {{ \Carbon\Carbon::parse($reservation->check_in)->diffInDays($reservation->check_out) }}</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="line"><span class="bold">Estatus:</span> {{ match($reservation->status) {
                'pending' => 'Pendiente',
                'confirmed' => 'Confirmada',
                'checked_in' => 'Check-in Realizado',
                'checked_out' => 'Check-out Realizado',
                'cancelled' => 'Cancelada',
                default => $reservation->status
            } }}</div>
        </div>

        <div class="divider"></div>

        <div class="center">
            <div class="line bold large">TOTAL: ${{ number_format($reservation->total_amount, 2) }}</div>
        </div>

        <div class="divider"></div>

        <div class="ticket-footer">
            <p>¡Gracias por su preferencia!</p>
            <p>Política de cancelación: 24 horas antes del check-in</p>
        </div>
    </div>
</body>
</html>
