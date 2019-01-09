<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 01/07/2019
 * Time: 11:38 AM
 */
?>

bài toán sử dụng const [] được fix cứng mà có thể bị thay thế bằng db trong tương lai

- Sử dụng Indexes cho nhũng column hay đươc select hoặc ORDER BY
- Khai báo kiểu size nhỏ nhất có thể (TinyInt, MediumInt..)
- Khai báo NOT NULL nếu có thể
- Nếu bắt buộc phải set NUll thì ko dùng default value cho column ấy nữa
- Không nên dùng 1 big table mà chia thành nhiều small table
- Nên sử dụng kiểu number thay cho kiểu string, vì number tốn ít bytes hơn string
- Khi so sánh giá trị giữa các column thì nên khai báo thêm character set and collation nếu có thể để tránh trường hợp conversion khi chạy query
- Với những column có size nhỏ hơn 8KB thì nên sử dụng VARCHAR thay thế BLOB
- Giảm thiểu sự kết nối tới hệ quản trị cơ sở dữ liệu, hãy chỉ sử dụng một số ít kết nối tới CSDL, mỗi kết nối sẽ xử lý nhiều nghiệp vụ hơn
- Chấp nhận dư thừa dữ liệu
- Chỉ dùng DISTINCT, UNION khi cần (Dùng EXISTS thay cho DISTRICT khi join các bảng có mối quan hệ 1-n)
- Tương tự, nếu một số join làm chậm truy vấn của bạn trong MySQL, có thể ngắt truy vấn thành hai hoặc nhiều câu lệnh và thực thi chúng một cách riêng biệt trong PHP
- Don’t use DISTINCT when you have or could use GROUP BY
- Don’t use ORDER BY RAND() if you have > ~2K records
- avoid wildcards at the start of LIKE queries
- Sử dụng EXPLAIN kết hợp với SHOW WARNINGS để phân tích truy vấn

Note:
- Ngày nay đa số các ngôn ngữ lập trình sẽ tự động giải phóng bộ nhớ sau khi chạy toàn bộ chương trình.
- InnoDB
Ưu điểm lớn của InnoDB là ở khả năng ghi dữ liệu cực nhanh do sử dụng cơ chế Row Level Locking cho phép cập nhật nhiều bản ghi cùng lúc.
Gọi là thua MyISAM về khả năng đọc, nhưng điều đó không có nghĩa là InnoDB quá chậm. Tuy nhiên, điều đáng quan tâm ở đây lại là việc InnoDB tiêu tốn quá nhiều ram khi chạy.
Chính bởi vậy, việc quá nhiều truy vấn ập đến dễ khiến cho MySQL “chết không kịp ngáp” do thiếu tài nguyên hoạt động,
từ đó nảy sinh nên lỗi trắng trang thần thánh mà giang hồ vẫn thường đồn đại.
- MySQL đang đọc toàn bộ bảng từ disk, tăng tỷ lệ I / O và đặt tải trên CPU. Điều này được biết đến như là "full table scan".
- Nếu server của bạn không có MySQL query caching theo mặc định, MySQL sẽ lưu giữ một record lưu lại tất cả các câu lệnh được thực hiện cùng với kết quả,
và nếu một câu lệnh giống hệt nhau được thực hiện thì các kết quả được lưu trong cache sẽ được trả về. Bộ nhớ cache sẽ không bị lỗi thời, vì MySQL xóa cache khi các bảng được thay đổi.

http://nongdanit.info/php-mysql/toi-uu-hoa-cau-truy-van-tren-he-quan-tri-mysql.html
- * Thủ thuật 1: INSERT
– Chúng ta có 2 bảng users (1 triệu records), messages (empty) với cấu trúc:
users(user_id – name – money)
messages( message_id – user_id – subject – body)
– Yêu cầu: bạn muốn gửi thông điệp đến tất cả các users có số money ít hơn 5 USD rằng: Tài khoản của bạn sắp hết! Hãy nộp thêm tiền vào tài khoản.
– Cách làm thường là:

$query = mysql_query("SELECT * FROM users WHERE money < 5");
$subject  = "Tài khoản của bạn sắp hết!";
while($row = db_fetch_object($query) ) {
$body = $row->name ."Tài khoản của bạn sắp hết! Hãy nộp thêm tiền vào tài khoản.";
mysql_query("INSERT INTO messages(user_id, subject, body) VALUES ($row->user_id, '$subject', '$body')");
}
// Processed in 67.0436019897 sec

– Cách làm tối ưu: dùng 1 query để giải quyết tình huống này
mysql_query("  INSERT INTO messages  (user_id, subject, body)  SELECT   user_id, ' Tài khoản của bạn sắp hết', CONCAT(name, ' Tài khoản của bạn sắp hết! Hãy nộp thêm tiền vào tài khoản.')  FROM users  WHERE money < 5 ");
// Processed in: 3.5900 sec
* Thủ thuật 2: UPDATE
– Chúng ta có 2 bảng users (1 triệu records), user_scores (2 triệu records)

users(user_id , name , total_scores , max_scores_can_contain)
user_scores(user_score_id, user_id,  score_type_id,  scores)
– Yêu cầu: một user sẽ được cộng thêm 1 số điểm là scores trong bảng user_scores tương ứng với mỗi score_type_id (ưu tiên theo score_type_id) mà user đang có. Nhưng tổng số scores hiện có và scores của các score_type_id này không được vượt quá con số max_scores_can_contain trong bảng users, nếu vượt quá thì chỉ lấy số scores tương ứng với tổng số scores bằng max_scores_can_contain.

Giải quyết :

Cách thường:
// Query tat ca users, chi update nhung user co scores > 0
$query = mysql_query("SELECT * FROM user_scores WHERE scores > 0");
while ( $row = mysql_fetch_object($query) ) { // Lay object cua user nay
$user = mysql_fetch_object(mysql_query("SELECT * FROM users WHERE user_id = $row->user_id"));
// Chi cong nhung user cos total_scores < max_scores_can_contain
if ( $user->total_scores < $user->max_scores_can_contain ) {
// Bat dau kiem tra bien scores_addition se cong vao
if ( $user->total_scores + $row->scores >= $user->max_scores_can_contain ) {
// Chi cong vao de total scores = max scores can contain
$scores_addition = $user->max_scores_can_contain - $user->total_scores;
} else {
// Cong binh thuong
$scores_addition = $row->scores;
} // Bat dau cong

mysql_query("UPDATE users SET total_scores = total_scores + $scores_addition WHERE user_id = $user->user_id");
}
}
// Processed in 530.916620016 sec

Cách tối ưu:
mysql_query(" UPDATE users AS u  LEFT JOIN user_scores AS us   ON u.user_id = us.user_id SET u.total_scores = u.total_scores +  (  CASE  WHEN (u.total_scores + us.scores) > u.max_scores_can_contain  THEN (u.max_scores_can_contain - u.total_scores)  ELSE us.scores  END  ) WHERE u.total_scores < u.max_scores_can_contain  AND us.scores > 0  ");
// Processed in 59.2287611961 sec