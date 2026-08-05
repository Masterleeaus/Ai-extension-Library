import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:pusher_beams/pusher_beams.dart';

import '../backend/services/notification_service.dart';
import '../backend/utils/maintenance_dialog.dart';
import '../controller/app_settings/app_settings_controller.dart';
import '../language/language_controller.dart';
import '../routes/routes.dart';
import '../utils/theme.dart';

class TitanHubApp extends StatefulWidget {
  const TitanHubApp({super.key});

  @override
  State<TitanHubApp> createState() => _TitanHubAppState();
}

class _TitanHubAppState extends State<TitanHubApp> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    _initializePushNotifications();
  }

  Future<void> _initializePushNotifications() async {
    if (kIsWeb) return;

    await PusherBeams.instance.onMessageReceivedInTheForeground(
      _onMessageReceivedInTheForeground,
    );
    await PusherBeams.instance.getInitialMessage();
  }

  void _onMessageReceivedInTheForeground(Map<Object?, Object?> data) {
    NotificationService.showLocalNotificationPusher(
      title: data['title']?.toString() ?? 'Titan Hub',
      body: data['body']?.toString() ?? '',
    );
  }

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(414, 896),
      builder: (_, child) => GetMaterialApp(
        title: 'Titan Hub',
        debugShowCheckedModeBanner: false,
        theme: Themes.light,
        darkTheme: Themes.dark,
        themeMode: Themes().theme,
        navigatorKey: Get.key,
        initialRoute: Routes.splashScreen,
        getPages: Routes.list,
        initialBinding: BindingsBuilder(
          () {
            Get.put(LanguageController());
            Get.put(AppSettingsController(), permanent: true);
            Get.put(SystemMaintenanceController());
          },
        ),
        builder: (context, widget) {
          ScreenUtil.init(context);
          final languageController = Get.find<LanguageController>();
          return Obx(
            () => MediaQuery(
              data: MediaQuery.of(context).copyWith(
                textScaler: const TextScaler.linear(1),
              ),
              child: Directionality(
                textDirection: languageController.isLoading
                    ? TextDirection.ltr
                    : languageController.languageDirection,
                child: widget!,
              ),
            ),
          );
        },
      ),
    );
  }
}
