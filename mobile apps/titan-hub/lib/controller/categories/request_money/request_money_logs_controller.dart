import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/model/request_money/request_money_log_model.dart';
import '../../../backend/services/request_money_api_services.dart';

class RequestMoneyLogsController extends GetxController
    with RequestMoneyApiServices {
  @override
  void onInit() {
    getRequestMoneyLogData();
    super.onInit();
  }

  RxString target = ''.obs;
  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;
  final _isRejectLoading = false.obs;
  bool get isRejectLoading => _isRejectLoading.value;
  final _isApproveLoading = false.obs;
  bool get isApproveLoading => _isApproveLoading.value;

  /// Request money logs process api
  late RequestMoneyLogModel _requestMoneyLogModel;
  RequestMoneyLogModel get requestMoneyInfoModel => _requestMoneyLogModel;
  Future<RequestMoneyLogModel> getRequestMoneyLogData() async {
    _isLoading.value = true;
    update();
    await getRequestMoneyLogApi().then((value) {
      _requestMoneyLogModel = value!;
      update();
    }).catchError((onError) {
      //TODOlog.e(onError);
    });

    _isLoading.value = false;
    update();
    return _requestMoneyLogModel;
  }

  /// Request money logs reject process
  late CommonSuccessModel _logRejectModel;
  CommonSuccessModel get logRejectModel => _logRejectModel;
  Future<CommonSuccessModel> logRejectProcessApi() async {
    _isRejectLoading.value = true;
    Map<String, dynamic> inputBody = {
      'target': target.value,
    };
    update();

    await rejectRequestMoneyApi(body: inputBody).then((value) {
      _logRejectModel = value!;
      update();
    }).catchError(
      (e) {},
    );

    _isRejectLoading.value = false;
    update();
    return _logRejectModel;
  }

  /// Request money logs reject process
  late CommonSuccessModel _logApproveModel;
  CommonSuccessModel get logApproveModel => _logApproveModel;
  Future<CommonSuccessModel> logApproveProcessApi() async {
    _isApproveLoading.value = true;
    Map<String, dynamic> inputBody = {
      'target': target.value,
    };
    update();

    await approveRequestMoneyApi(body: inputBody).then((value) {
      _logApproveModel = value!;
      update();
    }).catchError(
      (e) {},
    );

    _isApproveLoading.value = false;
    update();
    return _logApproveModel;
  }
}
