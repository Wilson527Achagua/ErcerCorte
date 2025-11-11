// Script de configuración para MongoDB
// Ejecutar con: mongosh < setup/mongodb-setup.js
// O copiar y pegar en mongosh

// Declarar variables db y use
const use = (dbName) => {
  db.getSiblingDB(dbName)
}
const db = use("inventory_system")

// Crear colección de usuarios con validación
db.createCollection("users", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["username", "password", "email", "created_at"],
      properties: {
        username: {
          bsonType: "string",
          description: "Nombre de usuario - requerido",
        },
        password: {
          bsonType: "string",
          description: "Contraseña hasheada - requerido",
        },
        email: {
          bsonType: "string",
          pattern: "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$",
          description: "Email válido - requerido",
        },
        created_at: {
          bsonType: "date",
          description: "Fecha de creación - requerido",
        },
      },
    },
  },
})

// Crear colección de clientes con validación
db.createCollection("clients", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["document_type", "document_number", "full_name", "contact", "email", "created_at"],
      properties: {
        document_type: {
          enum: ["CC", "NIT", "CE", "Pasaporte"],
          description: "Tipo de documento",
        },
        document_number: {
          bsonType: "string",
          description: "Número de documento - requerido",
        },
        full_name: {
          bsonType: "string",
          description: "Nombre completo - requerido",
        },
        contact: {
          bsonType: "string",
          description: "Teléfono de contacto - requerido",
        },
        email: {
          bsonType: "string",
          description: "Email - requerido",
        },
        created_at: {
          bsonType: "date",
          description: "Fecha de creación - requerido",
        },
      },
    },
  },
})

// Crear colección de productos con validación
db.createCollection("products", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["product_id", "name", "description", "quantity", "unit_value", "created_at"],
      properties: {
        product_id: {
          bsonType: "string",
          description: "ID único del producto - requerido",
        },
        name: {
          bsonType: "string",
          description: "Nombre del producto - requerido",
        },
        description: {
          bsonType: "string",
          description: "Descripción del producto - requerido",
        },
        quantity: {
          bsonType: "int",
          minimum: 0,
          description: "Cantidad en stock - requerido",
        },
        unit_value: {
          bsonType: "double",
          minimum: 0,
          description: "Valor unitario - requerido",
        },
        image: {
          bsonType: "string",
          description: "Ruta de la imagen",
        },
        created_at: {
          bsonType: "date",
          description: "Fecha de creación - requerido",
        },
      },
    },
  },
})

// Crear colección de ventas con validación
db.createCollection("sales", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["client_id", "products", "subtotal", "tax", "total", "created_at"],
      properties: {
        client_id: {
          bsonType: "objectId",
          description: "ID del cliente - requerido",
        },
        products: {
          bsonType: "array",
          description: "Array de productos vendidos",
          items: {
            bsonType: "object",
            required: ["product_id", "name", "quantity", "unit_value", "total"],
            properties: {
              product_id: {
                bsonType: "objectId",
                description: "ID del producto",
              },
              name: {
                bsonType: "string",
                description: "Nombre del producto",
              },
              quantity: {
                bsonType: "int",
                minimum: 1,
                description: "Cantidad vendida",
              },
              unit_value: {
                bsonType: "double",
                description: "Valor unitario",
              },
              total: {
                bsonType: "double",
                description: "Total del producto",
              },
            },
          },
        },
        subtotal: {
          bsonType: "double",
          description: "Subtotal sin IVA - requerido",
        },
        tax: {
          bsonType: "double",
          description: "IVA 19% - requerido",
        },
        total: {
          bsonType: "double",
          description: "Total con IVA - requerido",
        },
        created_at: {
          bsonType: "date",
          description: "Fecha de la venta - requerido",
        },
      },
    },
  },
})

// Crear índices para mejorar el rendimiento
print("Creando índices...")

// Índices para usuarios
db.users.createIndex({ username: 1 }, { unique: true })
db.users.createIndex({ email: 1 }, { unique: true })

// Índices para clientes
db.clients.createIndex({ document_number: 1 }, { unique: true })
db.clients.createIndex({ email: 1 })
db.clients.createIndex({ full_name: 1 })

// Índices para productos
db.products.createIndex({ product_id: 1 }, { unique: true })
db.products.createIndex({ name: 1 })

// Índices para ventas
db.sales.createIndex({ client_id: 1 })
db.sales.createIndex({ created_at: -1 })

print("✓ Índices creados correctamente")

// Crear usuario administrador por defecto
// Contraseña: admin123 (hasheada con password_hash de PHP)
print("Creando usuario administrador...")

db.users.insertOne({
  username: "admin",
  password: "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi", // admin123
  email: "admin@sistema.com",
  created_at: new Date(),
})

print("✓ Usuario administrador creado")
print("  Usuario: admin")
print("  Contraseña: admin123")

// Insertar datos de ejemplo (opcional)
print("\nInsertando datos de ejemplo...")

// Cliente de ejemplo
db.clients.insertOne({
  document_type: "CC",
  document_number: "1234567890",
  full_name: "Juan Pérez García",
  contact: "3001234567",
  email: "juan.perez@email.com",
  created_at: new Date(),
})

print("✓ Cliente de ejemplo creado")

// Productos de ejemplo
db.products.insertMany([
  {
    product_id: "PROD001",
    name: "Laptop Dell Inspiron 15",
    description: "Laptop con procesador Intel Core i5, 8GB RAM, 256GB SSD",
    quantity: 10,
    unit_value: 2500000.0,
    image: "",
    created_at: new Date(),
  },
  {
    product_id: "PROD002",
    name: "Mouse Logitech MX Master 3",
    description: "Mouse inalámbrico ergonómico con sensor de alta precisión",
    quantity: 25,
    unit_value: 350000.0,
    image: "",
    created_at: new Date(),
  },
  {
    product_id: "PROD003",
    name: "Teclado Mecánico Keychron K2",
    description: "Teclado mecánico inalámbrico con switches Blue",
    quantity: 15,
    unit_value: 450000.0,
    image: "",
    created_at: new Date(),
  },
  {
    product_id: "PROD004",
    name: "Monitor LG 27 pulgadas 4K",
    description: "Monitor IPS 4K con HDR10 y FreeSync",
    quantity: 8,
    unit_value: 1800000.0,
    image: "",
    created_at: new Date(),
  },
  {
    product_id: "PROD005",
    name: "Webcam Logitech C920",
    description: "Cámara web Full HD 1080p con micrófono estéreo",
    quantity: 20,
    unit_value: 280000.0,
    image: "",
    created_at: new Date(),
  },
])

print("✓ Productos de ejemplo creados")

print("\n========================================")
print("CONFIGURACIÓN COMPLETADA EXITOSAMENTE")
print("========================================")
print("\nBase de datos: inventory_system")
print("Colecciones creadas: users, clients, products, sales")
print("\nCredenciales de acceso:")
print("  Usuario: admin")
print("  Contraseña: admin123")
print("\n¡El sistema está listo para usar!")
print("========================================")
