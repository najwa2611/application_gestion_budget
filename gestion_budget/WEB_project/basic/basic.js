// basic.js - Complete Version with Image-Matching Design
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const addBudgetForm = document.getElementById('add-budget-item-form');
    const setTotalBudgetForm = document.getElementById('set-total-budget-form');
    const totalBudgetInput = document.getElementById('total-budget-input');
    const categorySelect = document.getElementById('category');
    const customCategoryInput = document.getElementById('custom-category');
    const amountInput = document.getElementById('amount');
    const spentInput = document.getElementById('spent');
    const budgetItemsContainer = document.getElementById('budget-items-container');
    const totalBudgetElement = document.getElementById('total-budget');
    const totalSpentElement = document.getElementById('total-spent');
    const remainingElement = document.getElementById('remaining');
    const searchInput = document.getElementById('search');
    const sortAmountBtn = document.getElementById('sort-amount');
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const closeSidebarBtn = document.querySelector('.close-sidebar-btn');

    // State variables
    let sortDirection = 'asc';
    let currentlyEditingId = null;

    // Initialize the app
    init();

    // Event Listeners
    function initEventListeners() {
        if (addBudgetForm) addBudgetForm.addEventListener('submit', handleAddBudgetItem);
        if (setTotalBudgetForm) setTotalBudgetForm.addEventListener('submit', handleSetTotalBudget);
        if (categorySelect) categorySelect.addEventListener('change', handleCategoryChange);
        if (searchInput) searchInput.addEventListener('input', filterBudgetItems);
        if (sortAmountBtn) sortAmountBtn.addEventListener('click', toggleSortDirection);
        if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
    }

    // Sidebar functionality
    function toggleSidebar() {
        if (sidebar.classList.contains('closed')) {
            sidebar.classList.remove('closed');
            sidebarToggle.classList.add('closed');
            sidebar.classList.add('active');
        } else {
            sidebar.classList.toggle('active');
        }
    }

    function closeSidebar() {
        sidebar.classList.add('closed');
        sidebar.classList.remove('active');
        sidebarToggle.classList.add('closed');
    }

    // Form handlers
    function handleSetTotalBudget(e) {
        e.preventDefault();
        
        const totalBudget = parseFloat(totalBudgetInput.value);
        
        if (isNaN(totalBudget)) {
            showAlert('Please enter a valid number', 'error');
            return;
        }
        
        if (totalBudget <= 0) {
            showAlert('Budget amount must be greater than 0', 'error');
            return;
        }
        
        setTotalBudgetForm.submit();
    }

    function handleAddBudgetItem(e) {
        e.preventDefault();
        
        let category = categorySelect.value;
        if (category === 'Other') {
            category = customCategoryInput.value.trim();
            if (!category) {
                showAlert('Please enter a custom category name', 'error');
                return;
            }
        } else if (!category) {
            showAlert('Please select a category', 'error');
            return;
        }
        
        const amount = parseFloat(amountInput.value);
        const spent = parseFloat(spentInput.value);
        
        if (isNaN(amount)) {
            showAlert('Please enter a valid budget amount', 'error');
            return;
        }
        
        if (amount <= 0) {
            showAlert('Budget amount must be greater than 0', 'error');
            return;
        }
        
        if (isNaN(spent)) {
            showAlert('Please enter a valid spent amount', 'error');
            return;
        }
        
        if (spent < 0) {
            showAlert('Spent amount cannot be negative', 'error');
            return;
        }
        
        if (spent > amount) {
            showAlert('Amount spent cannot exceed the budget amount', 'error');
            return;
        }
        
        if (currentlyEditingId) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'item_id';
            hiddenInput.value = currentlyEditingId;
            addBudgetForm.appendChild(hiddenInput);
        }
        
        addBudgetForm.submit();
    }

    function handleCategoryChange() {
        customCategoryInput.style.display = this.value === 'Other' ? 'block' : 'none';
        customCategoryInput.required = this.value === 'Other';
    }

    // Budget items display (matches your image)
    function renderBudgetItems() {
        if (!budgetItemsContainer) return;

        const items = document.querySelectorAll('.budget-item-card');
        if (items.length === 0) {
            budgetItemsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-coins"></i>
                    <h3>No Budget Items Added</h3>
                    <p>Start by adding your first budget item above</p>
                </div>
            `;
            return;
        }

        budgetItemsContainer.innerHTML = '';

        items.forEach(item => {
            const category = item.querySelector('.budget-item-title').textContent;
            const date = item.querySelector('.budget-item-date').textContent;
            const amounts = item.querySelectorAll('.budget-item-amounts span');
            
            // NEW: Debugging output to see what we're actually parsing
            console.log("Raw amounts text:");
            amounts.forEach(amount => console.log(amount.textContent));
            
            // More robust parsing function
            const parseMoney = (text) => {
                // Remove all non-numeric characters except decimal point
                const numericString = text.replace(/[^0-9.]/g, '');
                const value = parseFloat(numericString);
                return isNaN(value) ? 0 : value;
            };

            const budgetText = amounts[0].textContent;
            const spentText = amounts[1].textContent;
            
            const budgetAmount = parseMoney(budgetText);
            const spentAmount = parseMoney(spentText);
            
            // Debug output
            console.log(`Parsed values - Budget: ${budgetAmount}, Spent: ${spentAmount}`);
            
            // Calculate percentage with safety checks
            const percentage = budgetAmount > 0 ? (spentAmount / budgetAmount) * 100 : 0;
            const percentageDisplay = Math.min(100, Math.round(percentage)); // Cap at 100%
            
            // Debug output
            console.log(`Calculated percentage: ${percentage}%`);

            const newItem = document.createElement('div');
            newItem.className = 'budget-item-display';
            newItem.innerHTML = `
                <div class="budget-item-header">
                    <h3>${category} ${date}</h3>
                    <div class="budget-item-actions">
                        <button class="action-btn edit-item" data-id="${item.dataset.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-item" data-id="${item.dataset.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="budget-item-amounts">
                    <span>Budget: $${budgetAmount.toFixed(2)}</span>
                    <span>Spent: $${spentAmount.toFixed(2)}</span>
                    <span>Remaining: $${(budgetAmount - spentAmount).toFixed(2)}</span>
                </div>
                <div class="progress-container">
                    <div class="progress-labels">
                        <span>0%</span>
                        <span>${percentageDisplay}%</span>
                        <span>100%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${percentage}%"></div>
                    </div>
                </div>
                <div class="budget-item-divider"></div>
            `;
            
            budgetItemsContainer.appendChild(newItem);
        });

        setupEditDeleteButtons();
    }

    // Edit and Delete functionality
    function setupEditDeleteButtons() {
        document.querySelectorAll('.edit-item').forEach(btn => {
            btn.addEventListener('click', function() {
                editBudgetItem(this.dataset.id);
            });
        });
        
        document.querySelectorAll('.delete-item').forEach(btn => {
            btn.addEventListener('click', function() {
                confirmDeleteItem(this.dataset.id);
            });
        });
    }

    function editBudgetItem(itemId) {
        const itemCard = document.querySelector(`.budget-item-card[data-id="${itemId}"]`);
        if (!itemCard) return;
        
        const category = itemCard.querySelector('.budget-item-title').textContent.split(' ')[0];
        const amounts = itemCard.querySelectorAll('.budget-item-amounts span');
        const budgetAmount = parseFloat(amounts[0].textContent.replace('Budget: $', ''));
        const spentAmount = parseFloat(amounts[1].textContent.replace('Spent: $', ''));
        
        let isCustomCategory = true;
        for (let option of categorySelect.options) {
            if (option.value === category) {
                isCustomCategory = false;
                break;
            }
        }
        
        if (isCustomCategory) {
            categorySelect.value = 'Other';
            customCategoryInput.value = category;
        } else {
            categorySelect.value = category;
        }
        handleCategoryChange();
        
        amountInput.value = budgetAmount;
        spentInput.value = spentAmount;
        
        currentlyEditingId = itemId;
        
        const submitButton = addBudgetForm.querySelector('button[type="submit"]');
        submitButton.innerHTML = '<i class="fas fa-save"></i> Update Budget Item';
        
        addBudgetForm.scrollIntoView({ behavior: 'smooth' });
        showAlert('Item loaded for editing. Make your changes and click Update.', 'info');
    }

    function confirmDeleteItem(itemId) {
        showConfirmDialog(
            'Delete Budget Item',
            'Are you sure you want to delete this budget item? This action cannot be undone.',
            'Delete',
            'Cancel',
            () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_budget_item.php';
                
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'item_id';
                hiddenInput.value = itemId;
                
                form.appendChild(hiddenInput);
                document.body.appendChild(form);
                form.submit();
            }
        );
    }

    // Search and sort functionality
    function filterBudgetItems() {
        const searchTerm = searchInput.value.toLowerCase();
        const items = document.querySelectorAll('.budget-item-display');
        
        if (items.length === 0) return;
        
        let hasResults = false;
        
        items.forEach(item => {
            const category = item.querySelector('.budget-item-header h3').textContent.toLowerCase();
            if (category.includes(searchTerm)) {
                item.style.display = 'block';
                hasResults = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        if (!hasResults) {
            budgetItemsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Results Found</h3>
                    <p>Try a different search term</p>
                </div>
            `;
        }
    }

    function toggleSortDirection() {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        sortAmountBtn.innerHTML = `<i class="fas fa-sort-amount-${sortDirection === 'asc' ? 'down' : 'up'}"></i> Sort`;
        sortBudgetItems();
    }

    function sortBudgetItems() {
        const items = Array.from(document.querySelectorAll('.budget-item-display'));
        if (items.length === 0) return;
        
        items.sort((a, b) => {
            const amountA = parseFloat(a.querySelector('.budget-item-amounts span:first-child').textContent.replace('Budget: $', ''));
            const amountB = parseFloat(b.querySelector('.budget-item-amounts span:first-child').textContent.replace('Budget: $', ''));
            return sortDirection === 'asc' ? amountA - amountB : amountB - amountA;
        });
        
        items.forEach(item => budgetItemsContainer.appendChild(item));
    }

    // Notification system
    function showAlert(message, type) {
        const existingAlert = document.querySelector('.notification');
        if (existingAlert) existingAlert.remove();
        
        const alert = document.createElement('div');
        alert.className = `notification ${type}`;
        alert.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                          type === 'error' ? 'fa-exclamation-circle' : 
                          type === 'warning' ? 'fa-exclamation-triangle' : 
                          'fa-info-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    function showConfirmDialog(title, message, confirmText, cancelText, onConfirm) {
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog-overlay';
        dialog.innerHTML = `
            <div class="confirm-dialog">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="dialog-actions">
                    <button class="btn btn-secondary cancel-btn">${cancelText}</button>
                    <button class="btn btn-danger confirm-btn">${confirmText}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
        
        dialog.querySelector('.confirm-btn').addEventListener('click', () => {
            onConfirm();
            dialog.remove();
        });
        
        dialog.querySelector('.cancel-btn').addEventListener('click', () => {
            dialog.remove();
        });
    }

    // Initialize the app
    function init() {
        initEventListeners();
        if (categorySelect) {
            categorySelect.addEventListener('change', handleCategoryChange);
            handleCategoryChange.call(categorySelect);
        }
        renderBudgetItems();
    }
});

// Add dynamic styles for the budget items display
const budgetItemStyles = document.createElement('style');
budgetItemStyles.textContent = `
    /* Budget Items Display - Matching Your Image */
    .budget-item-display {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
    }
    
    .budget-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .budget-item-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }
    
    .budget-item-amounts {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }
    
    .budget-item-amounts span {
        color: var(--text-secondary);
    }
    
    .progress-container {
        margin: 0.5rem 0;
    }
    
    .progress-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: var(--text-tertiary);
        margin-bottom: 0.25rem;
    }
    
    .progress-bar {
        height: 6px;
        background-color: var(--dark-surface);
        border-radius: 3px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 3px;
    }
    
    .budget-item-divider {
        height: 1px;
        background-color: rgba(212, 175, 55, 0.1);
        margin-top: 1rem;
    }
    
    .budget-item-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .action-btn {
        background: none;
        border: none;
        color: var(--text-tertiary);
        cursor: pointer;
        font-size: 0.9rem;
        transition: color 0.2s;
    }
    
    .action-btn:hover {
        color: var(--primary-light);
    }
    
    .action-btn.edit-item:hover {
        color: var(--success);
    }
    
    .action-btn.delete-item:hover {
        color: var(--danger);
    }

    /* Confirm Dialog Styles */
    .confirm-dialog-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }
    
    .confirm-dialog {
        background-color: var(--dark-surface-light);
        border-radius: var(--border-radius);
        padding: var(--spacing-lg);
        width: 90%;
        max-width: 400px;
        box-shadow: var(--shadow-lg);
    }
    
    .confirm-dialog h3 {
        margin-bottom: var(--spacing-md);
        color: var(--text-primary);
    }
    
    .confirm-dialog p {
        margin-bottom: var(--spacing-lg);
        color: var(--text-secondary);
    }
    
    .dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: var(--spacing-md);
    }
    
    .btn {
        padding: var(--spacing-sm) var(--spacing-md);
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transition: var(--transition-fast);
        border: none;
    }
    
    .btn-secondary {
        background: transparent;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }
    
    .btn-danger {
        background: var(--danger);
        color: white;
    }
    
    /* Notification Styles */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        animation: slideIn 0.3s ease-out forwards;
        transform: translateX(120%);
    }
    
    .notification i {
        font-size: 1.2rem;
    }
    
    .notification.success {
        background-color: #4BB543;
        color: white;
    }
    
    .notification.error {
        background-color: #FF3333;
        color: white;
    }
    
    .notification.warning {
        background-color: #FFA500;
        color: white;
    }
    
    .notification.info {
        background-color: #4361ee;
        color: white;
    }
    
    @keyframes slideIn {
        to { transform: translateX(0); }
    }
`;
document.head.appendChild(budgetItemStyles);