import 'package:flutter/widgets.dart';

import 'app/titan_hub_app.dart';
import 'app/titan_hub_bootstrap.dart';

Future<void> main() async {
  await TitanHubBootstrap.initialize();
  runApp(const TitanHubApp());
}
