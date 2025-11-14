<?php
require_once '../config/session.php';
requireLogin();
require_once '../config/database.php';

$db = new Database();
$products = $db->executeQuery('products', [], ['sort' => ['nombre' => 1]]);

// *** NUEVO CÓDIGO CLOUDINARY PARA TRANSFORMAR LA IMAGEN ***
// Definimos la transformación: ancho 300, alto 180, recorte de llenado.
const CLOUDINARY_TRANSFORMATION = 'w_300,h_180,c_fill'; 
// *********************************************************
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Sistema de Inventario</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .search-bar {
            background: white;
            border-radius: 16px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 15px;
            color: #333;
        }
        
        .search-bar input::placeholder {
            color: #999;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15);
        }
        
        .product-card.expanded {
            grid-column: span 2;
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffffff 0%, #ffffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .product-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .product-stock {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .stock-high {
            background: linear-gradient(135deg, #8df77fff 0%, #0bff02ff 100%);
            color: white;
        }
        
        .stock-low {
            background: linear-gradient(135deg, #ee7f25ff 0%, #f00202ff 100%);
            color: white;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .product-details {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            animation: slideDown 0.3s ease;
        }
        
        .product-card.expanded .product-details {
            display: block;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            color: #1a1a1a;
        }
        
        .action-buttons-card {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-card {
            flex: 1;
            min-width: 80px;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            font-weight: 520;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-stock {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-stock:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .btn-delete:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(250, 112, 154, 0.4);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">Gestión de Productos</h1>
                <a href="create.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nuevo Producto
                </a>
            </div>
            
            <div class="search-bar">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="searchInput" placeholder="Buscar por ID, nombre del producto..." onkeyup="filterProducts()">
            </div>
            
            <div class="products-grid" id="productsGrid">
                <?php foreach ($products as $product): ?>
                <?php 
                    // Obtener la URL base
                    $base_url = htmlspecialchars($product->imagen ?? '');
                    $imageUrl = 'placeholder.png'; // Valor por defecto
                    
                    // Si tenemos una URL de Cloudinary (contiene 'res.cloudinary.com'), aplicamos la transformación
                    if (!empty($base_url) && strpos($base_url, 'res.cloudinary.com') !== false) {
                        // Inyectar la transformación en la URL
                        $imageUrl = str_replace('/upload/', "/upload/" . CLOUDINARY_TRANSFORMATION . "/", $base_url);
                    }
                ?>
                <div class="product-card" data-id="<?php echo htmlspecialchars($product->id_producto); ?>" data-name="<?php echo htmlspecialchars($product->nombre); ?>" onclick="toggleCard(this)">
                    <div class="product-image">
                        <?php if ($imageUrl !== 'placeholder.png'): ?>
                            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($product->nombre); ?>">
                        <?php else: ?>
                            Sin imagen
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="product-name"><?php echo htmlspecialchars($product->nombre); ?></h3>
                    
                    <div class="product-info">
                        <span class="product-stock <?php echo $product->cantidad < 10 ? 'stock-low' : 'stock-high'; ?>">
                            Stock: <?php echo $product->cantidad; ?>
                        </span>
                        <span class="product-price">$<?php echo number_format($product->valor_unitario, 2); ?></span>
                    </div>
                    
                    <div class="product-details">
                        <div class="detail-row">
                            <span class="detail-label">ID Producto:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($product->id_producto); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Descripción:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($product->descripcion); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Cantidad en Stock:</span>
                            <span class="detail-value"><?php echo $product->cantidad; ?> unidades</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Valor Unitario:</span>
                            <span class="detail-value">$<?php echo number_format($product->valor_unitario, 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Valor Total:</span>
                            <span class="detail-value">$<?php echo number_format($product->cantidad * $product->valor_unitario, 2); ?></span>
                        </div>
                        
                        <div class="action-buttons-card" onclick="event.stopPropagation()">
                            <a href="edit.php?id=<?php echo $product->_id; ?>" class="btn-card btn-edit">Editar</a>
                            <a href="add-stock.php?id=<?php echo $product->_id; ?>" class="btn-card btn-stock">+ Stock</a>
                            <a href="delete.php?id=<?php echo $product->_id; ?>" class="btn-card btn-delete" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="no-results" id="noResults" style="display: none;">
                No se encontraron productos que coincidan con tu búsqueda
            </div>
        </div>
    </div>
    
    <script>
        function toggleCard(card) {
            // Close all other cards
            document.querySelectorAll('.product-card').forEach(c => {
                if (c !== card) {
                    c.classList.remove('expanded');
                }
            });
            
            // Toggle current card
            card.classList.toggle('expanded');
        }
        
        function filterProducts() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const id = card.getAttribute('data-id').toLowerCase();
                const name = card.getAttribute('data-name').toLowerCase();
                
                if (id.includes(searchValue) || name.includes(searchValue)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }
    </script>
</body>
</html>
