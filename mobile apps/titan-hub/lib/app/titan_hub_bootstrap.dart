import 'package:flutter/widgets.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get_storage/get_storage.dart';

import '../backend/local_storage/local_storage.dart';
import '../backend/services/notification_service.dart';
import '../backend/utils/network_check/dependency_injection.dart';

abstract final class TitanHubBootstrap {
  static Future<void> initialize() async {
    WidgetsFlutterBinding.ensureInitialized();
    await ScreenUtil.ensureScreenSize();
    await GetStorage.init();
    await LocalStorages.initializeSecureSession();

    NotificationService.init();
    InternetCheckDependencyInjection.init();
  }
}
