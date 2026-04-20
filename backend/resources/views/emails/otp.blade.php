<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#f0f0f0; font-family: Arial, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f0; padding: 20px 0;">
    <tr>
      <td align="center">
        <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:4px; overflow:hidden;">

          <!-- HEADER -->
          <tr>
            <td style="background:#1a3a8c; padding: 28px 40px; text-align:right;">
              <h2 style="color:#ffffff; margin:0; font-size:22px; font-weight:bold;">
                Universidad Nacional del Altiplano
              </h2>
              <p style="color:#cdd8f0; margin:4px 0 0; font-size:14px;">
                Oficina de Tecnologías de la Información
              </p>
            </td>
          </tr>

          <!-- BODY -->
          <tr>
            <td style="padding: 36px 40px;">

              <!-- Saludo -->
              <h3 style="color:#1a3a8c; font-size:18px; margin:0 0 16px;">
                Estimado(a), {{ $nombre }}
              </h3>

              <p style="color:#444; font-size:14px; margin:0 0 24px; line-height:1.6;">
                Se ha procesado su solicitud de verificación de identidad en el 
                <strong>Sistema de Gestión de Inventariado</strong>. 
                A continuación, le brindamos la información relevante:
              </p>

              <!-- Caja azul - código -->
              <table width="100%" cellpadding="0" cellspacing="0"
                style="border-left: 4px solid #1a3a8c; background:#eef2fb; border-radius:0 4px 4px 0; margin-bottom:28px;">
                <tr>
                  <td style="padding: 20px 24px;">
                    <p style="color:#1a3a8c; font-weight:bold; font-size:14px; margin:0 0 10px;">
                      Código de verificación OTP:
                    </p>
                    <p style="font-size:36px; font-weight:bold; letter-spacing:10px; 
                               color:#1a3a8c; margin:0; font-family: monospace;">
                      {{ $codigo }}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Instrucciones -->
              <p style="color:#222; font-weight:bold; font-size:14px; margin:0 0 10px;">
                Instrucciones importantes:
              </p>
              <ul style="color:#444; font-size:14px; line-height:1.8; margin:0 0 24px; padding-left:20px;">
                <li>Este código es válido por <strong>5 minutos</strong>.</li>
                <li>Úselo únicamente en el sistema de inventariado de la UNAP.</li>
                <li>No comparta este código con nadie.</li>
              </ul>

              <!-- Caja amarilla - seguridad -->
              <table width="100%" cellpadding="0" cellspacing="0"
                style="border-left: 4px solid #f0a500; background:#fffbec; border-radius:0 4px 4px 0;">
                <tr>
                  <td style="padding: 16px 20px;">
                    <p style="color:#7a5500; font-weight:bold; font-size:14px; margin:0 0 8px;">
                      🔒 Medidas de seguridad:
                    </p>
                    <ul style="color:#7a5500; font-size:13px; line-height:1.8; margin:0; padding-left:18px;">
                      <li>No comparta sus credenciales por ningún medio.</li>
                      <li>Si no reconoce esta solicitud, ignórela.</li>
                      <li>El código expirará automáticamente.</li>
                    </ul>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="background:#f5f5f5; padding: 16px 40px; text-align:center; border-top:1px solid #e0e0e0;">
              <p style="color:#999; font-size:12px; margin:0;">
                Universidad Nacional del Altiplano Puno &copy; {{ date('Y') }} — Sistema de Gestión de Inventariado
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>