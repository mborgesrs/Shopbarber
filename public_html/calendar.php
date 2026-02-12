<?php
session_start(); if(!isset($_SESSION['user_id'])){ header('Location: login.php');exit; }
require_once __DIR__.'/../db.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$company_id = $_SESSION['company_id'];

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$dayFirstOfMonth = date('w', $firstDayOfMonth);

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthName = date('F', $firstDayOfMonth);
$monthsPt = [
    'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março', 
    'April' => 'Abril', 'May' => 'Maio', 'June' => 'Junho',
    'July' => 'Julho', 'August' => 'Agosto', 'September' => 'Setembro',
    'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
];
$displayMonth = $monthsPt[$monthName];

// Fetch quotes for the current month
$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
$endDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-$daysInMonth 23:59:59";
$stmt = $pdo->prepare("SELECT q.id, q.date_time, c.name as person_name FROM quotes q JOIN clients c ON c.id = q.client_id WHERE q.company_id = ? AND q.date_time BETWEEN ? AND ? AND q.status != 'Cancelado'");
$stmt->execute([$company_id, $startDate, $endDate]);
$quotes = $stmt->fetchAll();

$quotesByDay = [];
foreach ($quotes as $q) {
    $day = (int)date('j', strtotime($q['date_time']));
    $quotesByDay[$day][] = $q;
}

?>
<?php include __DIR__ . '/../views/header.php'; ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold flex items-center gap-3">
        <i class="fas fa-calendar-alt text-amber-500"></i>
        Agenda
    </h2>
    <div class="flex items-center gap-2">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <i class="fas fa-chevron-left text-gray-500"></i>
        </a>
        <span class="text-lg font-bold min-w-[150px] text-center"><?= $displayMonth ?> <?= $year ?></span>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <i class="fas fa-chevron-right text-gray-500"></i>
        </a>
    </div>
    <a href="quote_create.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-2">
        <i class="fas fa-plus text-xs"></i>
        Novo Agendamento
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50">
        <?php foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName): ?>
            <div class="py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-widest"><?= $dayName ?></div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-7">
        <?php
        // Previous month days
        for ($i = 0; $i < $dayFirstOfMonth; $i++) {
            echo '<div class="h-24 border-b border-r border-gray-50 bg-gray-50/30"></div>';
        }

        // Current month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isToday = $currentDate == date('Y-m-d');
            $hasQuotes = isset($quotesByDay[$day]);
            
            echo '<div class="day-cell h-24 border-b border-r border-gray-100 transition-colors group flex flex-col overflow-hidden">';
            echo '<a href="quote_create.php?date=' . $currentDate . '&from=calendar&month=' . $month . '&year=' . $year . '" class="p-2 hover:bg-blue-50/50 flex flex-col flex-1 overflow-hidden">';
            echo '<div class="flex justify-between items-start mb-1">';
            echo '<span class="text-sm font-bold ' . ($isToday ? 'bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center -ml-1 -mt-1 shadow-md shadow-blue-200' : 'text-gray-400') . '">' . $day . '</span>';
            echo '<i class="fas fa-plus text-[10px] text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity"></i>';
            echo '</div>';
            echo '</a>';
            
            if ($hasQuotes) {
                echo '<div class="space-y-1 px-2 pb-2">';
                foreach ($quotesByDay[$day] as $q) {
                    $time = date('H:i', strtotime($q['date_time']));
                    echo '<a href="quote_edit.php?id=' . $q['id'] . '&from=calendar&month=' . $month . '&year=' . $year . '" class="block text-[10px] leading-tight px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded border border-blue-100 truncate hover:bg-blue-100 transition-colors" title="' . $time . ' - ' . htmlspecialchars($q['person_name']) . '">';
                    echo '<span class="font-bold">' . $time . '</span> ' . htmlspecialchars($q['person_name']);
                    echo '</a>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        }

        // Remaining days to complete the last week
        $lastDayOfWeek = ($dayFirstOfMonth + $daysInMonth) % 7;
        if ($lastDayOfWeek != 0) {
            for ($i = $lastDayOfWeek; $i < 7; $i++) {
                echo '<div class="h-24 border-b border-r border-gray-50 bg-gray-50/30"></div>';
            }
        }
        ?>
    </div>
</div>

<style>
/* More aggressive scrollbar hiding */
.scrollbar-hide {
    scrollbar-width: none !important; /* Firefox */
    -ms-overflow-style: none !important;  /* IE and Edge */
}
.scrollbar-hide::-webkit-scrollbar {
    display: none !important; /* Chrome, Safari and Opera */
    width: 0 !important;
    height: 0 !important;
}

/* Ensure parents and main container don't show scrollbars */
main::-webkit-scrollbar { display: none !important; }
main { scrollbar-width: none !important; -ms-overflow-style: none !important; }

.day-cell {
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    overflow: hidden !important;
}
.day-cell::-webkit-scrollbar {
    display: none !important;
}
</style>

<?php include __DIR__ . '/../views/footer.php'; ?>
