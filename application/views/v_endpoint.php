<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<h1>Documentasi Endpoints E-Absensi</h1>
	<table border="1" cellspacing="0" cellpadding="8">
		<thead>
			<tr>
				<th>URI</th>
				<th>Method</th>
				<th>Parameter</th>
				<th>Input Post</th>
				<th>Keterangan</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $key): ?>
				<tr>
					<td><?= $key['uri'] ?></td>
					<td align="center"><?= $key['method'] ?></td>
					<td>
						<?php foreach ($key['parameter'] as $val): ?>
							<span>{<?= $val['name'] ?>} - <?= $val['title'] ?></span><br>
						<?php endforeach ?>
					</td>
					<td>
						<?php foreach ($key['post'] as $val): ?>
							<span>{<?= $val['name'] ?>} - <?= $val['title'] ?></span><br>
						<?php endforeach ?>
					</td>
					<td><?= $key['keterangan'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</body>
</html>