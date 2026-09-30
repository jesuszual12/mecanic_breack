# mecanic_breack

Aplicación Laravel 12 para recibir vehículos y administrar órdenes de trabajo, refacciones, cobros, gastos y personal desde un panel adaptable a web.


## Tecnologías utilizadas

- **Lenguajes:** PHP 8.2, JavaScript, HTML (plantillas Blade) y CSS.
- **Backend:** Laravel 12 
- **Frontend:** Blade, Tailwind CSS 4 y estilos CSS propios.
- **Base de datos:**  MySQL 
- **Herramientas:** Composer para dependencias de PHP, npm para dependencias de JavaScript y Vite 7 para compilar los recursos del frontend.


## Funciones implementadas

- Clientes y vehículos con placas, VIN y datos de contacto.
- Recepción y órdenes con estados, costos, anticipos, saldo, garantía e historial por vehículo.
- Conceptos de mano de obra/servicio y refacciones descontadas del inventario.
- Inventario con existencias mínimas, movimientos, proveedor y compras contabilizadas como gasto.
- Cobros por orden, gastos, cuentas por cobrar y pagos de nómina.
- Personal y cuentas de acceso con roles de administrador, encargado y técnico.
- Dashboard, búsqueda por orden/cliente/teléfono/placa, resumen anual y nota imprimible.
- Bitácora de operaciones asociada al usuario autenticado.

Los técnicos no pueden registrar movimientos financieros ni administrar cuentas. Encargados y administradores gestionan inventario y finanzas; solo el administrador crea cuentas.

## Alcance pendiente

Las cotizaciones con flujo de autorización, convenios empresariales con calendario propio, reportes descargables, respaldos automáticos, evidencia fotográfica y facturación CFDI/SAT no forman parte de esta primera entrega. Las deudas mostradas actualmente corresponden al saldo pendiente de órdenes de trabajo.

