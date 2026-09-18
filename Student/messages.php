<?php
/**
 * Internal Messaging System - Student Interface
 */
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/advanced_features.php';

if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'Student') {
    echo "<script>window.location = '../index.php'</script>";
    exit;
}

$admissionNo = $_SESSION['admissionNumber'];

// Handle new message submission
$messaging = new MessagingSystem($conn);
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $receiverId = SecurityHelper::sanitizeInput($_POST['receiverId']);
    $receiverRole = SecurityHelper::sanitizeInput($_POST['receiverRole']);
    $messageText = trim($_POST['message']);
    
    if (empty($messageText)) {
        $message = 'Message cannot be empty';
        $msgType = 'danger';
    } else {
        if ($messaging->sendMessage($admissionNo, 'Student', $receiverId, $receiverRole, $messageText)) {
            $message = 'Message sent successfully';
            $msgType = 'success';
        } else {
            $message = 'Error sending message';
            $msgType = 'danger';
        }
    }
}

// Get list of teachers and admins
$contactsResult = $conn->query("
    SELECT DISTINCT 'Teacher' as role, CONCAT(firstName, ' ', lastName) as name, emailAddress as contact
    FROM tblclassteacher
    UNION
    SELECT 'Admin' as role, CONCAT(firstName, ' ', lastName) as name, emailAddress as contact
    FROM tbladmin
");

$contacts = [];
while ($row = $contactsResult->fetch_assoc()) {
    $contacts[] = $row;
}

// Get recent conversations
$selectedContact = isset($_GET['contact']) ? SecurityHelper::sanitizeInput($_GET['contact']) : '';

if (!empty($selectedContact)) {
    $messaging->markAsRead($admissionNo);
    $conversationResult = $messaging->getConversation($admissionNo, $selectedContact, 50);
} else {
    $conversationResult = null;
}

unreadCount = $messaging->getUnreadCount($admissionNo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>Messages</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .messaging-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 0;
            height: 600px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .contacts-panel {
            background: #f9fafb;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
        }

        .contact-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .contact-item:hover,
        .contact-item.active {
            background: #667eea;
            color: white;
        }

        .contact-name {
            font-weight: 600;
        }

        .contact-role {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .chat-panel {
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
            font-weight: 600;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: #667eea;
            color: white;
        }

        .message.received .message-bubble {
            background: #e5e7eb;
            color: #1f2937;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.6;
            margin-top: 0.25rem;
        }

        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: white;
            display: flex;
            gap: 0.5rem;
        }

        .chat-input-area input {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }

        .unread-badge {
            display: inline-block;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .messaging-container {
                grid-template-columns: 1fr;
                height: auto;
            }

            .contacts-panel {
                display: none;
            }

            .message-bubble {
                max-width: 90%;
            }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'Includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'Includes/topbar.php'; ?>
                <div class="container-fluid" id="container-wrapper">
                    <h1 class="h3 mb-4 text-gray-800">
                        💬 Messages 
                        <?php if ($unreadCount > 0): ?>
                            <span class="unread-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </h1>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $msgType; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <div class="messaging-container">
                        <div class="contacts-panel">
                            <?php foreach ($contacts as $contact): ?>
                            <a href="?contact=<?php echo urlencode($contact['contact']); ?>" 
                               class="contact-item <?php echo ($selectedContact === $contact['contact']) ? 'active' : ''; ?>">
                                <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                                <div class="contact-role"><?php echo htmlspecialchars($contact['role']); ?></div>
                            </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="chat-panel">
                            <?php if (empty($selectedContact)): ?>
                            <div style="display: flex; align-items: center; justify-content: center; flex: 1; color: #9ca3af;">
                                <div style="text-align: center;">
                                    <i class="fas fa-comment-dots" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                    <p>Select a contact to start messaging</p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="chat-header"><?php echo htmlspecialchars($selectedContact); ?></div>

                            <div class="chat-messages" id="chatMessages">
                                <?php if ($conversationResult && $conversationResult->num_rows > 0): ?>
                                    <?php $messages = $conversationResult->fetch_all(MYSQLI_ASSOC); ?>
                                    <?php array_reverse($messages); ?>
                                    <?php foreach ($messages as $msg): ?>
                                    <div class="message <?php echo ($msg['senderId'] === $admissionNo) ? 'sent' : 'received'; ?>">
                                        <div>
                                            <div class="message-bubble"><?php echo htmlspecialchars($msg['message']); ?></div>
                                            <div class="message-time"><?php echo date('h:i A', strtotime($msg['createdAt'])); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="chat-input-area">
                                <input type="hidden" name="action" value="send">
                                <input type="hidden" name="receiverId" value="<?php echo htmlspecialchars($selectedContact); ?>">
                                <input type="hidden" name="receiverRole" value="<?php echo htmlspecialchars($_GET['role'] ?? 'Teacher'); ?>">
                                <input type="text" name="message" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'Includes/footer.php'; ?>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/ruang-admin.min.js"></script>

    <script>
        // Auto scroll to latest message
        const chatMessagesDiv = document.getElementById('chatMessages');
        if (chatMessagesDiv) {
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        // Auto refresh messages every 5 seconds
        setInterval(function() {
            location.reload();
        }, 5000);
    </script>
</body>
</html>
?>
