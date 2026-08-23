import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';
import 'package:qrpay/widgets/bottom_navbar/notification_widget.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../backend/utils/no_data_widget.dart';
import '../../controller/navbar/notification_controller.dart';
import '../../utils/responsive_layout.dart';

class NotificationScreen extends StatelessWidget {
  NotificationScreen({super.key});

  final controller = Get.put(NotificationController());

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        body: Obx(() => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context)),
      ),
    );
  }

  _bodyWidget(BuildContext context) {
    return controller.notificationModelData.data.notifications.isNotEmpty
        ? ListView.builder(
            physics: const BouncingScrollPhysics(),
            itemCount:
                controller.notificationModelData.data.notifications.length,
            itemBuilder: (context, index) {
              var data =
                  controller.notificationModelData.data.notifications[index];

              return NotificationWidget(
                subtitle: data.message,
                title: data.title,
                dateText: DateFormat.d().format(data.createdAt),
                monthText: DateFormat.MMM().format(data.createdAt),
              );
            },
          )
        : const NoDataWidget();
  }
}
