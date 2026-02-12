Módulo: Base de Conocimiento + Chatbot (simulado)
------------------------------------------------
Contenido del paquete:
- index.html       -> Interfaz web principal
- styles.css       -> Estilos CSS
- app.js           -> Lógica frontend (fetch al backend)
- backend.php      -> API PHP que lee/escribe data.json
- data.json        -> Datos temporales de ejemplo
- README.txt       -> Este archivo (instrucciones)

Requisitos:
- PHP instalado en tu computadora (>=7.0 recomendado)
- Un navegador web (Chrome, Edge, Firefox)

Ejecutar localmente (opción rápida con servidor PHP embebido):
1. Abre una terminal (cmd/PowerShell en Windows).
2. Navega a la carpeta donde descomprimiste el proyecto.
3. Ejecuta: php -S localhost:8000
4. Abre en el navegador: http://localhost:8000/index.html

Notas sobre permisos:
- Asegúrate que el servidor PHP tenga permisos de escritura en data.json para que las operaciones add/update/delete funcionen.
- En Windows normalmente no hay problema; en Linux/macOS ajusta permisos si es necesario: chmod 664 data.json

Seguridad y limitaciones:
- Este proyecto es una simulación para propósitos académicos.
- No implementa autenticación ni control de acceso.
- No use en producción sin añadir validación, sanitización y autenticación.

Si deseas, puedo:
- Convertir la API a rutas REST con control de acceso.
- Crear un instalador o guías específicas para XAMPP/WAMP.
- Añadir autenticación simulada para el formulario de edición.
