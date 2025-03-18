<?php

/**
 * Kelas utama untuk permainan catur
 * Menangani seluruh logika permainan termasuk pergerakan bidak, validasi, dan status permainan
 */
class ChessGame {
    private $board;    // Array 2D untuk menyimpan posisi bidak
    private $turn;     // Menyimpan giliran saat ini ('w' untuk putih, 'b' untuk hitam)
    private $history;  // Menyimpan riwayat gerakan untuk fitur undo

    /**
     * Constructor: Menginisialisasi permainan baru
     */
    public function __construct() {
        $this->initializeBoard();
        $this->turn = 'w'; // Putih selalu mulai duluan
        $this->history = [];
    }

    /**
     * Menginisialisasi papan catur dengan posisi awal bidak
     * Huruf kapital untuk bidak putih, huruf kecil untuk bidak hitam
     * R/r = Benteng, N/n = Kuda, B/b = Gajah, Q/q = Ratu, K/k = Raja, P/p = Pion
     */
    private function initializeBoard() {
        $this->board = [
            ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R'],  // Baris pertama putih
            ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],  // Pion putih
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],   // Baris kosong
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],   // Baris kosong
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],   // Baris kosong
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],   // Baris kosong
            ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],  // Pion hitam
            ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r']   // Baris pertama hitam
        ];
    }

    /**
     * Menampilkan papan catur ke layar dengan koordinat
     */
    public function displayBoard() {
        echo "  a b c d e f g h\n";
        echo " +----------------\n";
        for ($i = 0; $i < 8; $i++) {
            echo (8 - $i) . "|";
            for ($j = 0; $j < 8; $j++) {
                echo $this->board[$i][$j] . ' ';
            }
            echo "|\n";
        }
        echo " +----------------\n";
    }

    /**
     * Memindahkan bidak dari satu posisi ke posisi lain
     * @param string $from Posisi awal (contoh: "e2")
     * @param string $to Posisi tujuan (contoh: "e4")
     */
    public function movePiece($from, $to) {
        list($fromX, $fromY) = $this->parsePosition($from);
        list($toX, $toY) = $this->parsePosition($to);

        $piece = $this->board[$fromX][$fromY];
        
        // Memeriksa apakah ini giliran pemain yang benar
        if (($this->turn === 'w' && !ctype_upper($piece)) || 
            ($this->turn === 'b' && !ctype_lower($piece))) {
            echo "Bukan giliran Anda!\n";
            return;
        }

        if ($this->isValidMove($fromX, $fromY, $toX, $toY)) {
            // Menyimpan state papan sebelum gerakan untuk fitur undo
            $this->history[] = array_map(function($row) {
                return $row;
            }, $this->board);
            
            // Melakukan gerakan
            $this->board[$toX][$toY] = $this->board[$fromX][$fromY];
            $this->board[$fromX][$fromY] = ' ';

            // Memeriksa apakah gerakan menyebabkan raja sendiri dalam posisi skak
            if ($this->isCheck($this->turn)) {
                echo "Gerakan ini membuat raja Anda dalam posisi skak! Membatalkan...\n";
                $this->undoMove();
                return;
            }

            // Berganti giliran
            $this->turn = ($this->turn === 'w') ? 'b' : 'w';

            // Memeriksa apakah lawan dalam posisi skak atau skak mat
            if ($this->isCheck($this->turn)) {
                echo "Skak!\n";
                if ($this->isCheckmate($this->turn)) {
                    echo "Skak Mat! Permainan Berakhir.\n";
                    exit;
                }
            }
        } else {
            echo "Gerakan tidak valid! Coba lagi.\n";
        }
    }

    /**
     * Memeriksa apakah gerakan yang diinginkan valid
     */
    private function isValidMove($fromX, $fromY, $toX, $toY) {
        // Memeriksa apakah koordinat berada dalam papan
        if ($fromX < 0 || $fromX > 7 || $fromY < 0 || $fromY > 7 || 
            $toX < 0 || $toX > 7 || $toY < 0 || $toY > 7) {
            return false;
        }
        
        $piece = $this->board[$fromX][$fromY];
        $targetPiece = $this->board[$toX][$toY];
        
        // Tidak bisa menggerakkan kotak kosong
        if ($piece === ' ') {
            return false;
        }
        
        // Tidak bisa menangkap bidak sendiri
        if ($targetPiece !== ' ' && 
            (ctype_upper($piece) === ctype_upper($targetPiece))) {
            return false;
        }

        // Menentukan tipe bidak dan memvalidasi gerakan sesuai aturan
        $pieceType = strtolower($piece);
        switch ($pieceType) {
            case 'p': // Pion
                return $this->isValidPawnMove($fromX, $fromY, $toX, $toY);
            case 'r': // Benteng
                return $this->isValidRookMove($fromX, $fromY, $toX, $toY);
            case 'n': // Kuda
                return $this->isValidKnightMove($fromX, $fromY, $toX, $toY);
            case 'b': // Gajah
                return $this->isValidBishopMove($fromX, $fromY, $toX, $toY);
            case 'q': // Ratu
                return $this->isValidQueenMove($fromX, $fromY, $toX, $toY);
            case 'k': // Raja
                return $this->isValidKingMove($fromX, $fromY, $toX, $toY);
        }
        
        return false;
    }

    /**
     * Validasi gerakan pion
     */
    private function isValidPawnMove($fromX, $fromY, $toX, $toY) {
        // Menentukan arah gerakan (1 untuk putih, -1 untuk hitam)
        $direction = ctype_upper($this->board[$fromX][$fromY]) ? 1 : -1;
        $startRow = ctype_upper($this->board[$fromX][$fromY]) ? 1 : 6;
        
        // Gerakan maju normal
        if ($fromY === $toY && $this->board[$toX][$toY] === ' ') {
            if ($toX === $fromX + $direction) {
                return true;
            }
            // Langkah pertama bisa maju dua kotak
            if ($fromX === $startRow && $toX === $fromX + (2 * $direction) &&
                $this->board[$fromX + $direction][$fromY] === ' ') {
                return true;
            }
        }
        
        // Menangkap bidak secara diagonal
        if ($toX === $fromX + $direction && abs($toY - $fromY) === 1) {
            return $this->board[$toX][$toY] !== ' ';
        }
        
        return false;
    }

    /**
     * Validasi gerakan benteng
     */
    private function isValidRookMove($fromX, $fromY, $toX, $toY) {
        // Benteng hanya bisa bergerak horizontal atau vertikal
        if ($fromX !== $toX && $fromY !== $toY) return false;
        return $this->isPathClear($fromX, $fromY, $toX, $toY);
    }

    /**
     * Validasi gerakan kuda
     */
    private function isValidKnightMove($fromX, $fromY, $toX, $toY) {
        $dx = abs($toX - $fromX);
        $dy = abs($toY - $fromY);
        // Pola gerakan L: 2 kotak satu arah dan 1 kotak arah lain
        return ($dx === 2 && $dy === 1) || ($dx === 1 && $dy === 2);
    }

    /**
     * Validasi gerakan gajah
     */
    private function isValidBishopMove($fromX, $fromY, $toX, $toY) {
        // Gajah hanya bisa bergerak diagonal
        if (abs($toX - $fromX) !== abs($toY - $fromY)) return false;
        return $this->isPathClear($fromX, $fromY, $toX, $toY);
    }

    /**
     * Validasi gerakan ratu
     */
    private function isValidQueenMove($fromX, $fromY, $toX, $toY) {
        // Ratu bisa bergerak seperti benteng atau gajah
        return $this->isValidRookMove($fromX, $fromY, $toX, $toY) ||
               $this->isValidBishopMove($fromX, $fromY, $toX, $toY);
    }

    /**
     * Validasi gerakan raja
     */
    private function isValidKingMove($fromX, $fromY, $toX, $toY) {
        // Raja hanya bisa bergerak satu kotak ke segala arah
        return abs($toX - $fromX) <= 1 && abs($toY - $fromY) <= 1;
    }

    /**
     * Memeriksa apakah jalur antara posisi awal dan akhir bersih
     */
    private function isPathClear($fromX, $fromY, $toX, $toY) {
        $dx = $toX - $fromX;
        $dy = $toY - $fromY;
        
        // Menentukan langkah untuk setiap arah
        $stepX = ($dx === 0) ? 0 : $dx / abs($dx);
        $stepY = ($dy === 0) ? 0 : $dy / abs($dy);
        
        $currentX = $fromX + $stepX;
        $currentY = $fromY + $stepY;
        
        // Memeriksa setiap kotak di jalur
        while ($currentX !== $toX || $currentY !== $toY) {
            if ($this->board[$currentX][$currentY] !== ' ') {
                return false;
            }
            $currentX += $stepX;
            $currentY += $stepY;
        }
        
        return true;
    }

    /**
     * Memeriksa apakah raja dalam posisi skak
     */
    private function isCheck($color) {
        $king = ($color === 'w') ? 'K' : 'k';
        $kingPos = null;
        
        // Mencari posisi raja
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                if ($this->board[$i][$j] === $king) {
                    $kingPos = [$i, $j];
                    break 2;
                }
            }
        }
        
        if (!$kingPos) return false;
        
        // Memeriksa apakah ada bidak lawan yang bisa menyerang raja
        foreach ($this->board as $x => $row) {
            foreach ($row as $y => $piece) {
                if ($piece !== ' ' && 
                    (($color === 'w' && ctype_lower($piece)) || 
                     ($color === 'b' && ctype_upper($piece)))) {
                    if ($this->isValidMove($x, $y, $kingPos[0], $kingPos[1])) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Memeriksa apakah posisi saat ini adalah skak mat
     */
    private function isCheckmate($color) {
        if (!$this->isCheck($color)) {
            return false;
        }

        // Mencoba semua gerakan yang mungkin untuk keluar dari skak
        for ($fromX = 0; $fromX < 8; $fromX++) {
            for ($fromY = 0; $fromY < 8; $fromY++) {
                $piece = $this->board[$fromX][$fromY];
                if ($piece === ' ') continue;
                
                // Memeriksa apakah bidak milik pemain saat ini
                if (($color === 'w' && !ctype_upper($piece)) ||
                    ($color === 'b' && !ctype_lower($piece))) {
                    continue;
                }

                // Mencoba semua posisi tujuan yang mungkin
                for ($toX = 0; $toX < 8; $toX++) {
                    for ($toY = 0; $toY < 8; $toY++) {
                        if ($this->isValidMove($fromX, $fromY, $toX, $toY)) {
                            // Mencoba gerakan
                            $originalBoard = array_map(function($row) {
                                return $row;
                            }, $this->board);
                            
                            $this->board[$toX][$toY] = $this->board[$fromX][$fromY];
                            $this->board[$fromX][$fromY] = ' ';
                            
                            $stillInCheck = $this->isCheck($color);
                            
                            // Mengembalikan papan ke posisi semula
                            $this->board = $originalBoard;
                            
                            if (!$stillInCheck) {
                                return false; // Masih ada gerakan yang bisa menyelamatkan raja
                            }
                        }
                    }
                }
            }
        }
        
        return true; // Tidak ada gerakan yang bisa menyelamatkan raja
    }

    /**
     * Membatalkan gerakan terakhir
     */
    public function undoMove() {
        if (!empty($this->history)) {
            $this->board = array_pop($this->history);
            $this->turn = ($this->turn === 'w') ? 'b' : 'w';
            echo "Gerakan dibatalkan.\n";
        } else {
            echo "Tidak ada gerakan yang bisa dibatalkan!\n";
        }
    }

    /**
     * Mengubah notasi catur (e.g., "e2") menjadi koordinat array
     */
    private function parsePosition($pos) {
        $letters = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5, 'g' => 6, 'h' => 7];
        $x = 8 - intval($pos[1]);
        $y = $letters[$pos[0]];
        return [$x, $y];
    }
}

// Memeriksa ketersediaan fungsi PHP yang diperlukan
if (!function_exists('ctype_upper') || !function_exists('ctype_lower') || 
    !function_exists('ctype_alpha') || !function_exists('array_pop') || 
    !function_exists('trim') || !function_exists('fgets') || 
    !function_exists('explode') || !function_exists('count')) {
    die("Fungsi PHP yang diperlukan tidak tersedia. Mohon aktifkan ekstensi ctype dan standard PHP.\n");
}

// Memulai permainan
$game = new ChessGame();
while (true) {
    $game->displayBoard();
    echo "Masukkan gerakan (contoh: e2 e4) atau 'undo': ";
    $input = trim(fgets(STDIN));
    if ($input === 'exit') break;
    if ($input === 'undo') {
        $game->undoMove();
    } else {
        $moves = explode(' ', $input);
        if (count($moves) == 2) {
            $game->movePiece($moves[0], $moves[1]);
        } else {
            echo "Input tidak valid. Gunakan format: e2 e4 atau 'undo'\n";
        }
    }
}
